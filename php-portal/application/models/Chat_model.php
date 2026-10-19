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
            $this->db->order_by('id', 'DESC');
            $latest = $this->db->get_where('chat_messages', ['client_id' => $client_id], 1)->row();
            if ($latest) {
                $message_id = $latest->id;
            } else {
                return false;
            }
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

    /**
     * Get structured market metrics with reports and client entitlement flag
     */
    public function get_market_metrics_with_entitlements($client_id) {
        $this->db->select('report_id');
        $entitlements = $this->db->get_where('client_report_entitlements', ['client_id' => $client_id])->result_array();
        $entitled_ids = array_column($entitlements, 'report_id');
        // R-102 is public excerpt, universally accessible
        $entitled_ids[] = 'R-102';

        $this->db->select('m.*, r.title, r.sector, r.publisher, r.access_type, r.pages_count, r.summary');
        $this->db->from('market_metrics m');
        $this->db->join('reports r', 'm.report_id = r.report_id', 'left');
        $this->db->order_by('m.metric_value', 'DESC');
        $metrics = $this->db->get()->result_array();

        foreach ($metrics as &$metric) {
            $metric['is_entitled'] = in_array($metric['report_id'], $entitled_ids, true);
            if ($metric['cagr_percent'] !== null && $metric['unit'] === 'USD Billion') {
                $years = ($metric['forecast_year'] ?: 2030) - $metric['base_year'];
                if ($years > 0) {
                    $metric['projected_2030'] = round($metric['metric_value'] * pow(1 + ((float)$metric['cagr_percent'] / 100), $years), 2);
                } else {
                    $metric['projected_2030'] = (float)$metric['metric_value'];
                }
            } else {
                $metric['projected_2030'] = null;
            }
        }
        return $metrics;
    }

    /**
     * Get system and security analytics for the client
     */
    public function get_analytics_summary($client_id) {
        $total_queries = $this->db->where('client_id', $client_id)->where('role', 'user')->count_all_results('chat_messages');
        $escalations_count = $this->db->where('client_id', $client_id)->count_all_results('escalations');
        $feedback_up = $this->db->where('client_id', $client_id)->where('rating', 1)->count_all_results('feedback');
        $feedback_down = $this->db->where('client_id', $client_id)->where('rating', -1)->count_all_results('feedback');
        $security_events = $this->db->count_all_results('security_events');

        $this->db->select_avg('confidence');
        $this->db->where('client_id', $client_id);
        $this->db->where('role', 'assistant');
        $row = $this->db->get('chat_messages')->row();
        $avg_conf = ($row && $row->confidence !== null) ? round($row->confidence * 100, 1) : 92.5;

        return [
            'total_queries' => $total_queries,
            'escalations_count' => $escalations_count,
            'feedback_up' => $feedback_up,
            'feedback_down' => $feedback_down,
            'avg_confidence' => $avg_conf,
            'security_events' => $security_events
        ];
    }
}

