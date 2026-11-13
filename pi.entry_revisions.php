<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Entry Revisions Plugin for ExpressionEngine 7
 *
 * Outputs the saved revisions (from the entry versioning feature) of
 * channel entries on the front end.
 *
 * @package     ExpressionEngine
 * @subpackage  Addons
 * @category    Plugin
 * @author      Bhashkar Yadav
 * @link        https://uxi360.com/get-entry-revisions-in-expressionengine
 */
class Entry_revisions
{
    public $return_data = '';
    public function __construct()
    {
        $site_id = (int) ee()->TMPL->fetch_param('site_id', ee()->config->item('site_id'));
        $channel_ids = [];
        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $channel_ids = $this->getChannelIds($channel, $site_id);

            if (empty($channel_ids)) {
                $this->return_data = ee()->TMPL->no_results();
                return;
            }
        }

        $author_id = ee()->TMPL->fetch_param('author_id');
        $entry_id = ee()->TMPL->fetch_param('entry_id');
        $url_title = ee()->TMPL->fetch_param('url_title');
        $version_id = ee()->TMPL->fetch_param('version_id');
        $version_date = ee()->TMPL->fetch_param('version_date');
        $status = ee()->TMPL->fetch_param('status', 'open');

        $sort = strtolower((string) ee()->TMPL->fetch_param('version_date_sort', 'desc'));
        $sort = ($sort === 'asc') ? 'asc' : 'desc';

        $limit = (int) ee()->TMPL->fetch_param('limit', 10);
        $limit = ($limit > 0) ? $limit : 10;
        $offset = max(0, (int) ee()->TMPL->fetch_param('offset', 0));

        // Build query (Query Builder applies the configured table prefix)
        ee()->db->select('ev.version_id, ev.entry_id, ev.channel_id, ev.author_id, ev.version_date, ev.version_data')
            ->from('entry_versioning ev')
            ->join('channel_titles ct', 'ct.entry_id = ev.entry_id', 'inner')
            ->where('ct.site_id', $site_id);

        if (! empty($channel_ids)) {
            ee()->db->where_in('ev.channel_id', $channel_ids);
        }

        if ($author_id) {
            ee()->db->where_in('ev.author_id', $this->intList($author_id));
        }

        if ($entry_id) {
            ee()->db->where_in('ev.entry_id', $this->intList($entry_id));
        }

        if ($version_id) {
            ee()->db->where_in('ev.version_id', $this->intList($version_id));
        }

        if ($version_date) {
            ee()->db->where_in('ev.version_date', $this->intList($version_date));
        }

        if ($url_title) {
            ee()->db->where_in('ct.url_title', $this->stringList($url_title));
        }

        if ($status !== '' && strtolower($status) !== 'any') {
            if (strncasecmp($status, 'not ', 4) === 0) {
                ee()->db->where_not_in('ct.status', $this->stringList(substr($status, 4)));
            } else {
                ee()->db->where_in('ct.status', $this->stringList($status));
            }
        }

        $query = ee()->db->order_by('ev.version_date', $sort)
            ->order_by('ev.version_id', $sort)
            ->limit($limit, $offset)
            ->get();

        if ($query->num_rows() === 0) {
            $this->return_data = ee()->TMPL->no_results();
            return;
        }

        $fields = $this->getCustomFields($site_id);
        $total = $query->num_rows();
        $rows = [];
        $count = 0;

        foreach ($query->result_array() as $row) {
            $data = $this->unserializeVersion($row['version_data']);
            $count++;

            $vars = [
                'revision:title' => isset($data['title']) ? $data['title'] : '',
                'revision:url_title' => isset($data['url_title']) ? $data['url_title'] : '',
                'revision:count' => $count,
                'revision:total_counts' => $total,
                'revision:total_results' => $total,
                'revision:version_id' => $row['version_id'],
                'revision:version_date' => $row['version_date'],
                'revision:entry_id' => $row['entry_id'],
                'revision:channel_id' => $row['channel_id'],
                'revision:author_id' => $row['author_id'],
            ];

            foreach ($fields as $field_name => $field_id) {
                $key = 'field_id_' . $field_id;
                $vars['revision:' . $field_name] = isset($data[$key]) ? $this->scalarValue($data[$key]) : '';
            }

            $rows[] = $vars;
        }

        $query->free_result();

        $this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $rows);
    }

    private function getChannelIds($channel, $site_id)
    {
        $values = $this->stringList($channel);

        $query = ee()->db->select('channel_id, channel_name')
            ->from('channels')
            ->where('site_id', $site_id)
            ->get();

        $ids = [];
        foreach ($query->result_array() as $row) {
            if (in_array($row['channel_name'], $values, true) || in_array((string) $row['channel_id'], $values, true)) {
                $ids[] = (int) $row['channel_id'];
            }
        }

        return $ids;
    }

    private function getCustomFields($site_id)
    {
        $query = ee()->db->select('field_id, field_name')
            ->from('channel_fields')
            ->where_in('site_id', [0, $site_id])
            ->get();

        $fields = [];
        foreach ($query->result_array() as $row) {
            $fields[$row['field_name']] = (int) $row['field_id'];
        }

        return $fields;
    }

    private function unserializeVersion($serialized)
    {
        $data = @unserialize($serialized, ['allowed_classes' => false]);

        return is_array($data) ? $data : [];
    }

    private function scalarValue($value)
    {
        if (is_array($value)) {
            $flat = array_filter($value, 'is_scalar');
            if (count($flat) !== count($value)) {
                return '';
            }

            return implode(ee()->TMPL->fetch_param('multi_separator', ', '), $flat);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function stringList($param)
    {
        return array_values(array_filter(array_map('trim', explode('|', (string) $param)), 'strlen'));
    }

    private function intList($param)
    {
        return array_values(array_filter(array_map('intval', explode('|', (string) $param))));
    }
}