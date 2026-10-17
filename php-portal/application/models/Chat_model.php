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
     * Record thumbs up / thumbs down feedback
     */
    public function record_feedback($message_id, $conversation_id, $client_id, $rating, $comments = '') {
        $data = [
            'message_id' => $message_id,
            'conversation_id' => $conversation_id,
            'client_id' => $client_id,
            'rating' => (int)$rating,
            'comments' => $comments
        ];
        return $this->db->insert('feedback', $data);
    }
}
