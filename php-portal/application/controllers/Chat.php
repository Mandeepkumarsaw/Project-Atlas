<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

    protected $allowed_clients = ['C-01', 'C-02', 'C-03'];

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('api_client');
        $this->load->model('chat_model');
        $this->load->helper(['url', 'form', 'security']);
    }

    public function index() {
        // Enforce validated client ID
        $requested_client = $this->input->get('client_id', TRUE);
        if ($requested_client && in_array($requested_client, $this->allowed_clients, true)) {
            $client_id = $requested_client;
        } else {
            $client_id = $this->session->userdata('client_id');
            if (!$client_id || !in_array($client_id, $this->allowed_clients, true)) {
                $client_id = 'C-01';
            }
        }
        $this->session->set_userdata('client_id', $client_id);

        $conversation_id = $this->session->userdata('conversation_id');
        if (!$conversation_id) {
            $conversation_id = 'conv-' . substr(md5(uniqid(rand(), true)), 0, 8);
            $this->session->set_userdata('conversation_id', $conversation_id);
        }

        $messages = $this->session->userdata('chat_history') ?: [];
        if (empty($messages)) {
            $db_messages = $this->chat_model->get_conversation_messages($conversation_id, $client_id);
            if (!empty($db_messages)) {
                $messages = [];
                foreach ($db_messages as $row) {
                    $citations = !empty($row['citations_json']) ? json_decode($row['citations_json'], true) : [];
                    $conflicts = !empty($row['conflicts_json']) ? json_decode($row['conflicts_json'], true) : [];
                    $derived = !empty($row['derived_values_json']) ? json_decode($row['derived_values_json'], true) : [];
                    $messages[] = [
                        'id' => $row['id'],
                        'role' => $row['role'],
                        'content' => $row['content'],
                        'citations' => is_array($citations) ? $citations : [],
                        'confidence' => ($row['confidence'] !== null) ? (float)$row['confidence'] : 0.0,
                        'conflicts' => is_array($conflicts) ? $conflicts : [],
                        'derived_values' => is_array($derived) ? $derived : [],
                        'escalated' => ($row['status'] === 'escalated'),
                        'time' => date('H:i', strtotime($row['created_at']))
                    ];
                }
                $this->session->set_userdata('chat_history', $messages);
            }
        }
        $escalations = $this->api_client->get_escalations($client_id);

        $data = [
            'client_id' => $client_id,
            'conversation_id' => $conversation_id,
            'messages' => $messages,
            'escalations' => $escalations,
            'clients' => [
                'C-01' => 'Northwind Utilities (Enterprise - R-101, R-103)',
                'C-02' => 'Blue Delta Infra (Standard - R-103 Only)',
                'C-03' => 'Apex Energy Partners (Trial - Public Only)'
            ]
        ];

        $this->load->view('chat_view', $data);
    }

    public function switch_client() {
        $client_id = $this->input->post('client_id', TRUE);
        if ($client_id && in_array($client_id, $this->allowed_clients, true)) {
            $this->session->set_userdata('client_id', $client_id);
            // Clear current conversation on client switch to guarantee isolation
            $this->session->set_userdata('conversation_id', 'conv-' . substr(md5(uniqid(rand(), true)), 0, 8));
            $this->session->set_userdata('chat_history', []);
        }
        redirect('/chat');
    }

    public function send() {
        $question = trim($this->input->post('question', TRUE));
        $client_id = $this->session->userdata('client_id') ?: 'C-01';
        $conversation_id = $this->session->userdata('conversation_id') ?: 'conv-default';

        if (empty($question)) {
            redirect('/chat');
        }

        // Persist conversation to MySQL if not already created
        $this->chat_model->get_or_create_conversation($conversation_id, $client_id);

        // Save user message to MySQL
        $user_msg_id = $this->chat_model->save_chat_message(
            $conversation_id,
            $client_id,
            'user',
            $question
        );

        $history = $this->session->userdata('chat_history') ?: [];

        // Append user question
        $history[] = [
            'id' => $user_msg_id,
            'role' => 'user',
            'content' => $question,
            'time' => date('H:i')
        ];

        // Call Python FastAPI service via server-side cURL
        $response = $this->api_client->send_chat_query($client_id, $conversation_id, $question);

        if ($response && isset($response['success']) && $response['success']) {
            $asst_msg_id = $this->chat_model->save_chat_message(
                $conversation_id,
                $client_id,
                'assistant',
                $response['answer'],
                isset($response['citations']) ? $response['citations'] : [],
                isset($response['confidence']) ? $response['confidence'] : 0.0,
                isset($response['conflicts']) ? $response['conflicts'] : [],
                isset($response['derived_values']) ? $response['derived_values'] : [],
                !empty($response['escalated']) ? 'escalated' : 'success'
            );

            $history[] = [
                'id' => $asst_msg_id,
                'role' => 'assistant',
                'content' => $response['answer'],
                'citations' => isset($response['citations']) ? $response['citations'] : [],
                'confidence' => isset($response['confidence']) ? $response['confidence'] : 0.0,
                'conflicts' => isset($response['conflicts']) ? $response['conflicts'] : [],
                'derived_values' => isset($response['derived_values']) ? $response['derived_values'] : [],
                'escalated' => !empty($response['escalated']),
                'ticket_id' => isset($response['ticket_id']) ? $response['ticket_id'] : null,
                'time' => date('H:i')
            ];
        } else {
            $err_msg = isset($response['error']) ? $response['error'] : 'The report assistant is temporarily unavailable. Please retry later.';
            $asst_msg_id = $this->chat_model->save_chat_message(
                $conversation_id,
                $client_id,
                'assistant',
                $err_msg,
                [],
                0.0,
                [],
                [],
                'error'
            );

            $history[] = [
                'id' => $asst_msg_id,
                'role' => 'assistant',
                'content' => $err_msg,
                'citations' => [],
                'confidence' => 0.0,
                'conflicts' => [],
                'derived_values' => [],
                'escalated' => false,
                'time' => date('H:i')
            ];
        }

        $this->session->set_userdata('chat_history', $history);
        redirect('/chat');
    }

    public function clear() {
        $this->session->set_userdata('conversation_id', 'conv-' . substr(md5(uniqid(rand(), true)), 0, 8));
        $this->session->set_userdata('chat_history', []);
        redirect('/chat');
    }

    public function feedback() {
        $client_id = $this->session->userdata('client_id') ?: 'C-01';
        $conversation_id = $this->session->userdata('conversation_id') ?: 'conv-default';
        $message_id = (int)$this->input->post('message_id', TRUE);
        $rating = (int)$this->input->post('rating', TRUE);
        $comment = $this->input->post('comment', TRUE);

        if (!$message_id || !in_array($rating, [1, -1], true)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Invalid feedback parameters.']));
            return;
        }

        // Persist to MySQL with tenant ownership validation
        $saved = $this->chat_model->record_feedback($message_id, $conversation_id, $client_id, $rating, $comment);

        if ($saved) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'message' => 'Feedback successfully recorded in MySQL.']));
        } else {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Feedback rejected: unauthorized message or tenant mismatch.']));
        }
    }
}

