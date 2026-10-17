<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('api_client');
        $this->load->helper(['url', 'form', 'security']);
    }

    public function index() {
        // Default client is C-01 (Northwind Utilities) unless switched
        $client_id = $this->input->get('client_id') ?: $this->session->userdata('client_id') ?: 'C-01';
        $this->session->set_userdata('client_id', $client_id);

        $conversation_id = $this->session->userdata('conversation_id');
        if (!$conversation_id) {
            $conversation_id = 'conv-' . substr(md5(uniqid(rand(), true)), 0, 8);
            $this->session->set_userdata('conversation_id', $conversation_id);
        }

        $messages = $this->session->userdata('chat_history') ?: [];
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
        if ($client_id) {
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

        $history = $this->session->userdata('chat_history') ?: [];

        // Append user question
        $history[] = [
            'role' => 'user',
            'content' => $question,
            'time' => date('H:i')
        ];

        // Call Python FastAPI service
        $response = $this->api_client->send_chat_query($client_id, $conversation_id, $question);

        if ($response && isset($response['success']) && $response['success']) {
            $history[] = [
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
            $history[] = [
                'role' => 'assistant',
                'content' => isset($response['error']) ? $response['error'] : 'The report assistant is temporarily unavailable. Please retry later.',
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
        $rating = (int)$this->input->post('rating', TRUE);
        $comment = $this->input->post('comment', TRUE);
        // Returns JSON confirmation
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success', 'message' => 'Feedback recorded']));
    }
}
