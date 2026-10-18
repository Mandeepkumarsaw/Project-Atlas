<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get or create conversation for client
     */
    public function get_or_create_conversation($conversation_id, $client_id) {
        $query = $this->db->get_where('conversations', [
            'conversation_id' => $conversation_id,
            'client_id' => $client_id
        ]);
        if ($query->num_rows() === 0) {
            $this->db->insert('conversations', [
                'conversation_id' => $conversation_id,
                'client_id' => $client_id,
                'title' => 'Consultation ' . date('Y-m-d H:i')
            ]);
        }
        return $conversation_id;
    }

    /**
     * Get chat messages strictly scoped to client
     */
    public function get_conversation_messages($conversation_id, $client_id) {
        $this->db->where('conversation_id', $conversation_id);
        $this->db->where('client_id', $client_id);
        $this->db->order_by('created_at', 'ASC');
        $query = $this->db->get('chat_messages');
        return $query->result_array();
    }

    /**
     * Save a user or assistant chat message to MySQL chat_messages table
     */
    public function save_chat_message($conversation_id, $client_id, $role, $content, $citations = [], $confidence = null, $conflicts = [], $derived_values = [], $status = 'success') {
        $data = [
            'conversation_id' => $conversation_id,
            'client_id' => $client_id,
            'role' => $role,
            'content' => $content,
            'citations_json' => !empty($citations) ? json_encode($citations) : null,
            'confidence' => ($confidence !== null) ? (float)$confidence : null,
            'conflicts_json' => !empty($conflicts) ? json_encode($conflicts) : null,
            'derived_values_json' => !empty($derived_values) ? json_encode($derived_values) : null,
            'status' => $status
        ];
        $this->db->insert('chat_messages', $data);
        return $this->db->insert_id();
    }

    /**
     * Record thumbs up (+1) / thumbs down (-1) feedback with tenant validation
     */
    public function record_feedback($message_id, $conversation_id, $client_id, $rating, $comments = '') {
        // Enforce tenant boundary: verify message exists and belongs to the authenticated client
        $msg = $this->db->get_where('chat_messages', [
            'id' => (int)$message_id,
            'client_id' => $client_id
        ])->row();

        if (!$msg) {
            return false;
        }

        $data = [
            'message_id' => (int)$message_id,
            'conversation_id' => $conversation_id,
            'client_id' => $client_id,
            'rating' => ((int)$rating > 0) ? 1 : -1,
            'comments' => $comments ? substr(trim($comments), 0, 1000) : null
        ];
        return $this->db->insert('feedback', $data);
    }
}

