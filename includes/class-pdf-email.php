<?php
/**
 * WPI_PDF_Email — Professional PDF report generator for Audit4me.
 * Pure PHP, zero external dependencies. Proper selectable-text PDFs.
 * Supports: coloured header, section bars, Yes/No chips, signatures,
 * conditional logic child answers, photo thumbnails, flagged rows, notes.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_PDF_Email {

    const PW = 595.28;  // A4 width  (points)
    const PH = 841.89;  // A4 height (points)
    const ML = 36;      // margin left
    const MR = 36;      // margin right
    const MT = 36;      // margin top
    const MB = 48;      // margin bottom

    private $streams   = array();
    private $cur_page  = -1;
    private $y         = 0;
    private $font      = 'Helvetica';
    private $fsize     = 10;
    private $images    = array();
    private $cfg       = array();
    private $inspection = null;
    private $report_date = '';

    // ── Entry point ───────────────────────────────────────────────

    public static function get_pdf_file( $inspection_id ) {
        // Preferred path: reuse the rich HTML PDF engine so email attachments,
        // mobile share, and browser downloads all produce the same visual output.
        if ( class_exists( 'WPI_PDF' ) && method_exists( 'WPI_PDF', 'get_rich_pdf_file' ) ) {
            $rich = WPI_PDF::get_rich_pdf_file( $inspection_id );
            if ( $rich && file_exists( $rich ) ) {
                return $rich;
            }
        }

        // Last-resort fallback for hosts without wkhtmltopdf / headless Chrome.
        return self::get_legacy_pdf_file( $inspection_id );
    }

    public static function get_legacy_pdf_file( $inspection_id ) {
        global $wpdb;

        $ins = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                    t.title AS template_title, t.settings AS t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) AS inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID = i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id = i.conducted_by AND um_fn.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id = i.conducted_by AND um_ln.meta_key = 'last_name'
             WHERE i.id = %d", $inspection_id
        ) );
        if ( ! $ins ) return null;

        $qs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id = %d ORDER BY sort_order ASC",
            $ins->template_id
        ) );
        // Fallback for deleted templates — reconstruct from saved responses
        if ( empty($qs) ) {
            $saved_ids = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT question_id FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d ORDER BY id",
                $inspection_id
            ) );
            // Try to recover labels from wpi_actions
            $action_labels = array();
            $action_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT question_id, question_label FROM {$wpdb->prefix}wpi_actions WHERE inspection_id=%d AND question_label != ''",
                $inspection_id
            ) );
            foreach ( $action_rows as $ar ) {
                $action_labels[(int)$ar->question_id] = $ar->question_label;
            }
            $fake_sort = 0;
            foreach ( $saved_ids as $sr ) {
                if ( ! is_numeric($sr->question_id) ) continue;
                $fq = new stdClass();
                $fq->id    = (int)$sr->question_id;
                $fq->label = isset($action_labels[(int)$sr->question_id])
                    ? $action_labels[(int)$sr->question_id]
                    : 'Response Field '.($fake_sort+1);
                $fq->type = 'short_text'; $fq->section = 'Inspection Responses';
                $fq->sort_order = $fake_sort++; $fq->is_scored = 0;
                $fq->passing_answer = ''; $fq->options = null;
                $fq->logic = null; $fq->repeatable = 0; $fq->is_required = 0;
                $qs[] = $fq;
            }
        }

        $rs = $wpdb->get_results( $wpdb->prepare(
            "SELECT question_id, value, flagged, notes, photos FROM {$wpdb->prefix}wpi_responses WHERE inspection_id = %d",
            $inspection_id
        ) );

        $rmap = array(); $cmap = array(); $extra_gallery_photos = array();
        $repeat_rmap = array(); $sec_max_repeat = array();
        $qid_to_section = array(); $qid_to_label = array();
        foreach ( $qs as $q ) {
            $qid_to_section[(string)$q->id] = trim($q->section ?? '') ?: 'General';
            $qid_to_label[(string)$q->id] = $q->label ?? '';
        }
        foreach ( $rs as $r ) {
            $ph = $r->photos ? json_decode($r->photos, true) : array();
            $r->photos = is_array($ph) ? array_values(array_filter(array_map(function($x){
                return is_array($x)&&!empty($x['url'])?$x:null;
            }, $ph))) : array();
            if ( preg_match('/^__r(\d+)__(.+)$/', (string)$r->question_id, $m) ) {
                $ri  = (int) $m[1];
                $qid = $m[2];
                // Store the full response for this repeat instance
                if ( ! isset($repeat_rmap[$ri]) ) $repeat_rmap[$ri] = array();
                $repeat_rmap[$ri][$qid] = $r;
                // Track the highest repeat index per section
                $sec = $qid_to_section[$qid] ?? 'General';
                if ( ! isset($sec_max_repeat[$sec]) || $ri > $sec_max_repeat[$sec] ) {
                    $sec_max_repeat[$sec] = $ri;
                }
                continue;
            }
            if ( is_numeric($r->question_id) ) $rmap[(int)$r->question_id] = $r;
            else                               $cmap[$r->question_id]       = $r;
        }
        ksort($repeat_rmap);

        $self = new self();
        $self->build( $ins, $qs, $rmap, $cmap, $extra_gallery_photos, $repeat_rmap, $sec_max_repeat );
        $bytes = $self->render_pdf();

        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $ins->title ?: 'report');
        $path = sys_get_temp_dir() . '/' . 'wpi_' . $inspection_id . '_' . $safe . '.pdf';
        file_put_contents($path, $bytes);
        return $path;
    }



private function resolve_report_title( $ins, $cfg ) {
    $title_tpl = trim((string)($cfg['report_title'] ?? ''));
    if ( $title_tpl === '' ) {
        return (string) ($ins->template_title ?: $ins->title ?: 'Inspection Report');
    }
    $dt = null;
    foreach (array('completed_at','conducted_at') as $f) {
        if (!empty($ins->$f)) { try { $dt = new DateTime($ins->$f); break; } catch(Exception $e){} }
    }
    if (!$dt) $dt = new DateTime();
    $date_str = $dt->format('d M Y');
    $time_str = $dt->format('g:i A');

    global $wpdb;
    $conducted_on = '';
    if ( ! empty($ins->id) ) {
        $resp = $wpdb->get_var($wpdb->prepare(
            "SELECT value FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d AND question_id IN (
                SELECT id FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d AND type IN ('datetime','date_time') ORDER BY sort_order LIMIT 1
            ) LIMIT 1",
            $ins->id, $ins->template_id
        ));
        if ( is_string($resp) && $resp !== '' ) {
            $formatted = self::format_token_value_by_type( $resp, 'datetime' );
            $conducted_on = $formatted !== '' ? $formatted : $resp;
        }
    }

    // Build slug→value map from all responses so {field:*} tokens resolve
    $q_map_t = array();
    if ( !empty($ins->id) && !empty($ins->template_id) ) {
        global $wpdb;
        $field_qs = $wpdb->get_results($wpdb->prepare(
            "SELECT q.id, q.label, q.type, r.value FROM {$wpdb->prefix}wpi_questions q
             LEFT JOIN {$wpdb->prefix}wpi_responses r ON r.question_id = q.id AND r.inspection_id = %d
             WHERE q.template_id = %d AND q.type NOT IN ('instruction','page') ORDER BY q.sort_order",
            $ins->id, $ins->template_id
        ));
        foreach ($field_qs as $fq) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i','_', $fq->label ?? ''));
            $slug = trim($slug,'_');
            $slug = substr($slug,0,40);
            if ($slug && $fq->value !== null && $fq->value !== '') {
                $q_map_t[$slug] = self::format_token_value_by_type($fq->value, $fq->type ?? '');
            }
        }
    }
    // Seed {field:site} from site_name if no form field provided it
    if ( empty($q_map_t['site']) && !empty($ins->site_name) ) {
        $q_map_t['site'] = $ins->site_name;
    }
    // Seed conducted_on from inspection meta if not already set from form question
    if ( empty($conducted_on) && !empty($ins->conducted_at) ) {
        try {
            $dt_co = new DateTime($ins->conducted_at);
            $conducted_on = $dt_co->format('d M Y, g:i A');
        } catch(Exception $e) {}
    }
    if ( empty($q_map_t['conducted_on']) && $conducted_on ) {
        $q_map_t['conducted_on'] = $conducted_on;
    }
    if ( empty($q_map_t['prepared_by']) ) {
        $prep = trim(($ins->inspector_name ?? '') ?: ($ins->inspector_display ?? ''));
        if ($prep) $q_map_t['prepared_by'] = $prep;
    }
    if ( empty($q_map_t['audit_title']) || empty($q_map_t['audit_tittle']) ) {
        $at = $ins->title ?? '';
        if ($at) { $q_map_t['audit_title'] = $at; $q_map_t['audit_tittle'] = $at; }
    }

    $map = array(
        '{template}' => (string) ($ins->template_title ?: $ins->title ?: 'Inspection Report'),
        '{site}' => (string) ($ins->site_name ?? ''),
        '{date}' => $date_str,
        '{time}' => $time_str,
        '{field:conducted_on}' => $conducted_on,
    );
    $resolved = strtr($title_tpl, $map);
    // Resolve any remaining {field:slug} tokens
    $resolved = preg_replace_callback('/\{field:([^}]+)\}/', function($m) use ($q_map_t) {
        $t = trim($m[1]);
        if ( isset($q_map_t[$t]) ) return $q_map_t[$t];
        foreach ( $q_map_t as $k => $v ) { if ( strpos($k,$t)===0 ) return $v; }
        return '';
    }, $resolved);
    $resolved = preg_replace('/\s+/', ' ', trim((string)$resolved));
    return $resolved !== '' ? $resolved : (string) ($ins->template_title ?: $ins->title ?: 'Inspection Report');
}

    private function format_report_datetime( $value, $fallback_now = true ) {
        if ( ! empty( $value ) ) {
            try {
                return new DateTime( $value );
            } catch ( Exception $e ) {}
            $ts = strtotime( (string) $value );
            if ( $ts ) {
                $dt = new DateTime();
                $dt->setTimestamp( $ts );
                return $dt;
            }
        }
        return $fallback_now ? new DateTime() : null;
    }

    // ── Build ─────────────────────────────────────────────────────

    private function build( $ins, $qs, $rmap, $cmap, $extra_gallery_photos = array(), $repeat_rmap = array(), $sec_max_repeat = array() ) {
        $cfg = $ins->t_settings ? json_decode($ins->t_settings, true) : array();
        $cfg = is_array($cfg) ? $cfg : array();
        $cfg = array_merge(array(
            'header_color'      => '#1a3a5c',
            'header_text_color' => '#ffffff',
            'accent_color'      => '#1a3a5c',
            'logo_url'          => '',
            'logo_position'     => 'left',
            'report_title'      => '',
            'show_audit_title'  => true,
            'show_inspector'    => false,
            'show_date'         => false,
            'show_site'         => false,
            'show_score'        => false,
            'show_gallery'      => true,
            'footer_left'       => '{template}',
            'footer_center'     => '',
            'footer_right'      => 'Page {page} of {pages}',
            'footer_text'       => '',
        ), $cfg);
        $this->cfg = $cfg;
        $this->inspection = $ins;

        $this->new_page();

        $report_title = $this->resolve_report_title($ins, $cfg);

        $hcol = !empty($cfg['header_color']) ? $cfg['header_color'] : '#1a3a5c';
        list($hr,$hg,$hb) = $this->hex2rgb($hcol);
        $tcol = !empty($cfg['header_text_color']) ? $cfg['header_text_color'] : ($this->is_dark($hcol) ? '#ffffff' : '#111111');
        list($tr,$tg,$tb) = $this->hex2rgb($tcol);

        // ── iAuditor-style header: logo top-left, title below, status badge top-right ──
        $cw = self::PW - self::ML - self::MR;
        $header_y = self::MT;

        // Logo — top left, up to 120×48px
        if ( !empty($cfg['logo_url']) ) {
            $this->embed_url_image($cfg['logo_url'], self::ML, $header_y, 120, 48);
            $header_y += 54;
        } else {
            $header_y += 8;
        }

        // Report title — large bold, dark navy
        if ( !empty($report_title) ) {
            $this->set_font('Helvetica-Bold', 22);
            $title_lines = $this->wrap($report_title, $cw - 80, 22);
            foreach ( array_slice($title_lines, 0, 2) as $line ) {
                $this->put_text(self::ML, $header_y, $line, 17, 24, 39);
                $header_y += 26;
            }
            $header_y += 4;
        }

        // Subtitle — site / date / inspector on one line
        $subtitle_parts = array();
        $dt = null;
        foreach (array('completed_at','conducted_at') as $f) {
            if (!empty($ins->$f)) { try { $dt = new DateTime($ins->$f); break; } catch(Exception $e){} }
        }
        if (!$dt) $dt = new DateTime();
        $this->report_date = $dt->format('d M Y, g:i A');
        // Respect PDF settings toggles
        if (!empty($cfg['show_site'])      && !empty($ins->site_name))      $subtitle_parts[] = $ins->site_name;
        if (!empty($cfg['show_date']))                                        $subtitle_parts[] = $dt->format('d M Y, g:i A');
        if (!empty($cfg['show_inspector']) && !empty($ins->inspector_name)) $subtitle_parts[] = $ins->inspector_name;
        if (!empty($cfg['show_score'])     && $ins->score !== null)         $subtitle_parts[] = 'Score '.round($ins->score).'%';
        if (!empty($subtitle_parts)) {
            $this->set_font('Helvetica', 10);
            $subtitle = implode(' / ', $subtitle_parts);
            $this->put_text(self::ML, $header_y, $subtitle, 80, 90, 110);
            $header_y += 16;
        }

        // Status badge — top right aligned with title
        $status = !empty($ins->status) ? ucfirst(str_replace('_',' ',$ins->status)) : 'Complete';
        list($sr,$sg,$sb) = $status === 'Completed' || $status === 'Complete' ? array(22,163,74) : array(245,158,11);
        $badge_w = 60; $badge_h = 14;
        $badge_x = self::ML + $cw - $badge_w;
        $badge_y = self::MT + (empty($cfg['logo_url']) ? 8 : 54);
        $this->set_font('Helvetica-Bold', 9);
        $this->put_text($badge_x, $badge_y, strtoupper($status), $sr, $sg, $sb);

        // Divider line
        $header_y += 6;
        $this->draw_hline(self::ML, $header_y, $cw, 220, 225, 235);
        $header_y += 10;
        $this->y = $header_y;

        // ── Column layout ───────────────────────────────────────
        $cw = self::PW - self::ML - self::MR;
        $col_q = $cw * 0.60;
        $col_a = $cw - $col_q;

        $this->draw_table_header($cw, $col_q);

        $all_gallery_photos = array();
        $sections = array();
        $section_order = array();
        $section_pagebreak_before = array();
        $pending_page_break = false;

        foreach ( $qs as $q ) {
            if ( $q->type === 'page' ) {
                $pending_page_break = true;
                continue;
            }
            $sec = trim($q->section ?? '') ?: 'General';
            if ( ! isset($sections[$sec]) ) {
                $sections[$sec] = array();
                $section_order[] = $sec;
                $section_pagebreak_before[$sec] = $pending_page_break;
            }
            $sections[$sec][] = $q;
            $pending_page_break = false;
        }

        // ── Apply section show/hide conditions for PDF/email reports ──
        // This mirrors the web view: if a section is configured to show only
        // when a trigger answer matches, it is completely removed from the PDF
        // when it does not match. This also prevents unanswered hidden sections
        // from appearing in the report.
        $wpi_get_resp_value = function( $ref ) use ( $rmap ) {
            $ref = (string) $ref;
            if ( $ref === '' ) return '';
            if ( is_numeric( $ref ) && isset( $rmap[(int) $ref] ) ) {
                return (string) ( $rmap[(int) $ref]->value ?? '' );
            }
            foreach ( $rmap as $qid => $rr ) {
                if ( (string) $qid === $ref ) return (string) ( $rr->value ?? '' );
            }
            return '';
        };
        $wpi_rule_matches = function( $actual, $when ) {
            $actual = trim( (string) $actual );
            $when   = is_array( $when ) ? (string) ( $when['label'] ?? $when['value'] ?? '' ) : (string) $when;
            $when   = trim( $when );
            if ( $when === 'any' || $when === 'answered' ) return $actual !== '';
            if ( $when === 'empty' ) return $actual === '';
            return $actual === $when;
        };
        $wpi_logic_section_vis = array();
        foreach ( $sections as $sec_for_logic => $qs_for_logic ) {
            foreach ( $qs_for_logic as $logic_q ) {
                $logic = ! empty( $logic_q->logic ) ? json_decode( $logic_q->logic, true ) : array();
                if ( ! is_array( $logic ) ) continue;
                $qid = (int) ( $logic_q->id ?? 0 );
                $actual = ( $qid && isset( $rmap[$qid] ) ) ? (string) ( $rmap[$qid]->value ?? '' ) : '';
                foreach ( $logic as $rule ) {
                    if ( ! is_array( $rule ) || empty( $rule['section'] ) || empty( $rule['action'] ) ) continue;
                    if ( $rule['action'] !== 'show_section' && $rule['action'] !== 'hide_section' ) continue;
                    $target = (string) $rule['section'];
                    if ( ! isset( $wpi_logic_section_vis[$target] ) ) {
                        $wpi_logic_section_vis[$target] = array( 'hasShow' => false, 'showMatched' => false, 'hideMatched' => false );
                    }
                    $matched = $wpi_rule_matches( $actual, $rule['when'] ?? '' );
                    if ( $rule['action'] === 'show_section' ) {
                        $wpi_logic_section_vis[$target]['hasShow'] = true;
                        if ( $matched ) $wpi_logic_section_vis[$target]['showMatched'] = true;
                    } elseif ( $matched ) {
                        $wpi_logic_section_vis[$target]['hideMatched'] = true;
                    }
                }
            }
        }
        $wpi_should_show_section = function( $sec_name ) use ( $cfg, $wpi_get_resp_value, $wpi_logic_section_vis ) {
            $sec_name = (string) $sec_name;
            $base_sec = preg_replace( '/\s+#\d+$/', '', $sec_name );
            $conds = ( isset( $cfg['section_conditions'] ) && is_array( $cfg['section_conditions'] ) ) ? $cfg['section_conditions'] : array();
            $cond = $conds[$sec_name] ?? ( $conds[$base_sec] ?? null );
            if ( is_array( $cond ) && isset( $cond['value'] ) && trim( (string) $cond['value'] ) !== '' ) {
                $refs = array( $cond['question_db_id'] ?? '', $cond['question_id'] ?? '', $cond['question_key'] ?? '' );
                $actual = '';
                foreach ( $refs as $ref ) {
                    if ( (string) $ref === '' ) continue;
                    $actual = $wpi_get_resp_value( $ref );
                    if ( $actual !== '' ) break;
                }
                $match = trim( (string) $actual ) === trim( (string) $cond['value'] );
                $mode = isset( $cond['mode'] ) ? (string) $cond['mode'] : 'show';
                if ( $mode === 'hide' ) {
                    if ( $match ) return false;
                } else {
                    if ( ! $match ) return false;
                }
            }
            $v = $wpi_logic_section_vis[$sec_name] ?? ( $wpi_logic_section_vis[$base_sec] ?? null );
            if ( is_array( $v ) ) {
                if ( ! empty( $v['hideMatched'] ) ) return false;
                if ( ! empty( $v['hasShow'] ) && empty( $v['showMatched'] ) ) return false;
            }
            return true;
        };
        $section_order = array_values( array_filter( $section_order, function( $sec_name ) use ( $wpi_should_show_section, &$sections ) {
            if ( ! $wpi_should_show_section( $sec_name ) ) {
                unset( $sections[$sec_name] );
                return false;
            }
            return true;
        } ) );

        $odd = true;
        $photo_counter = 1; // global sequential photo number across all sections
        foreach ( $section_order as $sec ) {
            if ( ! empty($section_pagebreak_before[$sec]) ) {
                $this->new_page();
                $this->draw_table_header($cw, $col_q);
                $odd = true;
            }

            $this->render_section_heading($sec, $cw);
            foreach ( $sections[$sec] as $q ) {
                $this->render_question_row( $q, $rmap, $cmap, $cw, $col_q, $col_a, $odd, $all_gallery_photos, '', $photo_counter );
            }

            if ( ! empty($sec_max_repeat[$sec]) ) {
                $max_repeat = (int) $sec_max_repeat[$sec];
                $display_num = 1;
                for ( $ri = 1; $ri <= $max_repeat; $ri++ ) {
                    $repeat_map = isset($repeat_rmap[$ri]) && is_array($repeat_rmap[$ri]) ? $repeat_rmap[$ri] : array();
                    // Skip if THIS section's questions have no actual data at this repeat index
                    // Also check child responses (child_X_Y keys) so conditional children aren't missed
                    $has_data = false;
                    foreach ( $sections[$sec] as $q ) {
                        $qid = (string)$q->id;
                        if ( isset($repeat_map[$qid]) ) {
                            $rr = $repeat_map[$qid];
                            $v  = isset($rr->value) ? (string)$rr->value : '';
                            if ( $v !== '' || ! empty($rr->notes) || ! empty($rr->photos) ) {
                                $has_data = true;
                                break;
                            }
                        }
                        // Check child question responses for this parent
                        foreach ( $repeat_map as $rk => $rv ) {
                            if ( strpos((string)$rk, 'child_' . $qid . '_') === 0 ) {
                                $cv = isset($rv->value) ? (string)$rv->value : '';
                                if ( $cv !== '' || ! empty($rv->notes) || ! empty($rv->photos) ) {
                                    $has_data = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    if ( ! $has_data ) continue;
                    $display_num++;
                    $this->render_section_heading($sec . ' #' . $display_num, $cw);
                    foreach ( $sections[$sec] as $q ) {
                        $this->render_question_row( $q, $repeat_map, array(), $cw, $col_q, $col_a, $odd, $all_gallery_photos, '__r'.$ri.'__', $photo_counter );
                    }
                }
            }
        }

        if ( ! empty($extra_gallery_photos) ) {
            $all_gallery_photos = array_merge( $all_gallery_photos, $extra_gallery_photos );
        }
        if ( ! empty($cfg['show_gallery']) && ! empty($all_gallery_photos) ) {
            $seen = array(); $dedup = array();
            foreach ( $all_gallery_photos as $ph ) {
                $k = ($ph['url'] ?? '') . '|' . ($ph['section'] ?? '') . '|' . ($ph['label'] ?? '');
                if ( $k && ! isset($seen[$k]) ) { $seen[$k] = true; $dedup[] = $ph; }
            }
            $this->render_media_summary($dedup);
        }

        // ── Footers ──
        $total = count($this->streams);
        for ($i = 0; $i < $total; $i++) {
            $left   = $this->resolve_footer_tokens( !empty($cfg['footer_left']) ? $cfg['footer_left'] : ( !empty($cfg['footer_text']) ? $cfg['footer_text'] : '{template}' ), $i+1, $total );
            $center = $this->resolve_footer_tokens( $cfg['footer_center'] ?? '', $i+1, $total );
            $right  = $this->resolve_footer_tokens( !empty($cfg['footer_right']) ? $cfg['footer_right'] : 'Page {page} of {pages}', $i+1, $total );
            $this->put_footer($i, $left, $center, $right);
        }
    }


private function render_section_heading($title, $cw) {
    $this->need_space(18);
    $this->fill_rect(self::ML, $this->y, $cw, 15, 243,244,246);
    $this->draw_rect(self::ML, $this->y, $cw, 15, 209,213,219);
    $this->set_font('Helvetica-Bold', 9);
    $this->put_text(self::ML+6, $this->y+4, $title, 17,24,39);
    $this->y += 17;
}

private function render_question_row( $q, $response_map, $child_map, $cw, $col_q, $col_a, &$odd, &$all_gallery_photos, $child_prefix = '', &$photo_counter = null ) {
    if ($q->type === 'instruction') {
        $label = $q->label ?? '';
        $lines = $this->wrap($label, $cw - 16, 9);
        $line_h = 13;
        $box_h  = 16 + count($lines) * $line_h; // label row + text lines
        $this->need_space($box_h + 4);
        // Blue background box
        $this->fill_rect(self::ML, $this->y, $cw, $box_h, 239, 246, 255); // #eff6ff
        // Blue left border (4px)
        $this->fill_rect(self::ML, $this->y, 4, $box_h, 59, 130, 246);    // #3b82f6
        // Top/bottom border
        $this->draw_hline(self::ML, $this->y,          $cw, 191, 219, 254); // #bfdbfe
        $this->draw_hline(self::ML, $this->y + $box_h, $cw, 191, 219, 254);
        // "INFORMATION" label
        $this->set_font('Helvetica-Bold', 7);
        $this->put_text(self::ML + 10, $this->y + 5, 'INFORMATION', 29, 78, 216); // #1d4ed8
        // Text content
        $this->set_font('Helvetica', 9);
        $ty = $this->y + 14;
        foreach ($lines as $ln) {
            $this->put_text(self::ML + 10, $ty, $ln, 30, 58, 138); // #1e3a8a
            $ty += $line_h;
        }
        $this->y += $box_h + 4;
        return;
    }

    $qid = (string) $q->id;
    $r = isset($response_map[$qid]) ? $response_map[$qid] : (isset($response_map[(int)$q->id]) ? $response_map[(int)$q->id] : null);
    $vl = strtolower(trim((string)($r ? $r->value : '')));
    $v  = $r ? trim((string)$r->value) : '';
    $fl = $r && !empty($r->flagged);
    $nt = $r && !empty($r->notes) ? trim($r->notes) : '';
    $ph = $r && !empty($r->photos) ? $r->photos : array();

    if ( ! empty($ph) ) {
        if ( $photo_counter === null ) $photo_counter = 1;
        foreach ( $ph as $gph ) {
            if ( ! empty($gph['url']) ) {
                $all_gallery_photos[] = array(
                    'url'     => $gph['url'],
                    'label'   => $q->label ?? '',
                    'section' => trim($q->section ?? '') ?: 'General',
                    'num'     => $photo_counter++, // assign number here, shared with thumbnail
                );
            }
        }
    }

    if ($q->type === 'signature') {
        $this->render_sig($q->label ?? 'Signature', $v, $cw, $col_q);
        return;
    }

    $ll = $this->wrap($q->label ?? '', $col_q - 10, 9);
    $vd = $v ?: '—';
    if ( in_array( $q->type, array('datetime','date_time','date','time'), true ) ) {
        $formatted = self::format_token_value_by_type( $v, $q->type );
        if ( $formatted !== '' ) $vd = $formatted;
    }
    // Detect location JSON in any field type
    $location_map_data = null;
    if ( $vd !== '—' && substr(trim($vd),0,1) === '{' ) {
        $maybe_loc = json_decode($vd, true);
        if ( is_array($maybe_loc) && (isset($maybe_loc['lat']) || isset($maybe_loc['address'])) ) {
            if ( !empty($maybe_loc['address']) ) {
                $addr = preg_replace('/\s*\([-\d.]+,\s*[-\d.]+\)\s*$/', '', $maybe_loc['address']);
                $vd = trim($addr);
            } elseif ( isset($maybe_loc['lat']) && isset($maybe_loc['lng']) ) {
                $vd = $maybe_loc['lat'].', '.$maybe_loc['lng'];
            }
            if ( !empty($maybe_loc['lat']) && !empty($maybe_loc['lng']) ) {
                $location_map_data = array('lat' => $maybe_loc['lat'], 'lng' => $maybe_loc['lng']);
            }
        }
    }
    $is_long_val = (strlen(strip_tags($vd)) > 70) || in_array($q->type, array('textarea','long_text','short_text'));
    $vv = $is_long_val
        ? $this->wrap($vd, $cw - 20, 9)
        : $this->wrap($vd, $col_a - 10, 9);
    $nl = array();
    if ($nt) {
        $nl = $this->wrap('Note: '.$nt, $col_a - 10, 8);
        $vv = array_merge($vv, array(''), $nl);
    }

    if ($is_long_val) {
        // Long value: question label on top, answer below full width
        $row_h = (count($ll) * 13) + (count($vv) * 13) + 12;
    } else {
        $row_h = max(count($ll), count($vv)) * 13 + 8;
    }
    $photo_h = count($ph) > 0 ? 56 : 0;
    $this->need_space($row_h + $photo_h);

    if ($fl)        $this->fill_rect(self::ML, $this->y, $cw, $row_h+$photo_h, 255,243,205);
    elseif ($odd)   $this->fill_rect(self::ML, $this->y, $cw, $row_h+$photo_h, 249,250,251);

    if (!$is_long_val) $this->draw_vline(self::ML+$col_q, $this->y, $row_h);

    $this->set_font('Helvetica-Bold', 9);
    $ty = $this->y + 6;
    foreach ($ll as $ln) {
        $this->put_text(self::ML+5, $ty, $ln, 26,26,46);
        $ty += 13;
    }

    // Use custom yes_no_colors if available
    $col = null; $matched_color = null; $matched_text = null;
    if ($q->type === 'yes_no' && !empty($q->yes_no_colors)) {
        $ync = is_array($q->yes_no_colors) ? $q->yes_no_colors : json_decode((string)$q->yes_no_colors, true);
        $ync = is_array($ync) ? $ync : array();
        $ck  = $vl==='yes'?'yes':($vl==='no'?'no':'na');
        $col = isset($ync[$ck]) && $ync[$ck] ? $ync[$ck] : null;
        $tc_key = $ck.'_text';
        $tc = isset($ync[$tc_key]) && $ync[$tc_key] ? $ync[$tc_key] : '#ffffff';
        if ($col) { list($ar,$ag,$ab) = $this->hex2rgb($col); }
        else      { list($ar,$ag,$ab) = $this->ans_color($vl); }
        list($tr2,$tg2,$tb2) = $this->hex2rgb($tc);
    } elseif (in_array($q->type, array('multiple_choice','select','dropdown','checkbox','radio','multi_select'))
              && !empty($q->options)) {
        $opts = is_array($q->options) ? $q->options : json_decode((string)$q->options, true);
        $opts = is_array($opts) ? $opts : array();
        $matched_color = null;
        foreach ($opts as $opt) {
            if (is_array($opt) && !empty($opt['color'])) {
                $ol = strtolower(trim($opt['label'] ?? $opt['value'] ?? ''));
                if ($ol === strtolower($v)) { $matched_color = $opt['color']; break; }
            }
        }
        $matched_text = null;
        foreach ($opts as $opt) {
            if (is_array($opt) && !empty($opt['text_color'])) {
                $ol = strtolower(trim($opt['label'] ?? $opt['value'] ?? ''));
                if ($ol === strtolower($v)) { $matched_text = $opt['text_color']; break; }
            }
        }
        if ($matched_color) { list($ar,$ag,$ab) = $this->hex2rgb($matched_color); }
        else                 { list($ar,$ag,$ab) = $this->ans_color($vl); }
        list($tr2,$tg2,$tb2) = $matched_text ? $this->hex2rgb($matched_text) : array(255,255,255);
    } else {
        list($ar,$ag,$ab) = $this->ans_color($vl);
        list($tr2,$tg2,$tb2) = array(255,255,255);
    }
    if ($is_long_val) {
        // Answer goes below label, full width
        $ty += 2;
    } else {
        $ty = $this->y + 6;
    }
    $vi = 0;
    // note_start = index of blank separator before note lines
    // notes themselves start at note_start+1; blank is at note_start
    $note_start = $nl ? count($vv) - count($nl) - 1 : 9999;
    $ans_x = $is_long_val ? self::ML+5 : self::ML+$col_q+5;
    // Determine if we should render a coloured chip (has custom bg colour)
    $use_chip = ($matched_color) || ($col);
    foreach ($vv as $ln) {
        if ($vi > $note_start && $ln !== '') {
            // Note lines — grey, smaller font
            $this->set_font('Helvetica', 8);
            $this->put_text($ans_x, $ty, $ln, 100,100,110);
        } elseif ($vi === $note_start) {
            // Blank separator — skip rendering, just advance y
            $ty += 13; $vi++; continue;
        } elseif ($use_chip && $vi === 0 && $ln !== '') {
            $this->set_font('Helvetica-Bold', 9);
            $row_right  = self::ML + $cw;
            $ans_start  = self::ML + $col_q;
            $chip_x     = $ans_start;
            $chip_w     = $row_right - $chip_x;
            $chip_h     = 14;
            $chip_y     = $ty - 2;
            $this->fill_rect($chip_x, $chip_y, $chip_w, $chip_h, $ar, $ag, $ab);
            $this->put_text($chip_x + 5, $ty, $ln, 0, 0, 0);
        } else {
            $this->set_font('Helvetica', 9);
            $this->put_text($ans_x, $ty, $ln, $ar,$ag,$ab);
        }
        $ty += 13; $vi++;
    }

    if ($fl) {
        $this->set_font('Helvetica-Bold', 7);
        $this->put_text(self::ML+$cw-52, $this->y+4, '! FLAGGED', 180,80,0);
    }

    $this->y += $row_h;

    if (count($ph) > 0) {
        // Extract the pre-assigned photo numbers for this question's photos
        $q_photo_nums = array();
        $pushed = array_slice($all_gallery_photos, -count($ph));
        foreach ($pushed as $pushed_ph) {
            if (isset($pushed_ph['num'])) $q_photo_nums[] = $pushed_ph['num'];
        }
        $this->render_photos($ph, $q_photo_nums);
    }

    $this->draw_hline(self::ML, $this->y, $cw, 225,225,225);
    $this->y += 3;

    // Draw map snapshot for location fields
    if ( $location_map_data ) {
        $lat = $location_map_data['lat'];
        $lng = $location_map_data['lng'];
        $map_w = $cw - 20;
        $map_h = round($map_w * 250/600);
        // Geoapify Static Maps
        $map_url = "https://maps.geoapify.com/v1/staticmap?style=osm-bright&width=600&height=250&center=lonlat:{$lng},{$lat}&zoom=16&marker=lonlat:{$lng},{$lat};type:material;color:%23ff0000;size:large&apiKey=33bad1af2e854e8087f63ea08b2622a9";
        // Fallback to Yandex
        $map_urls = array(
            $map_url,
            "https://static-maps.yandex.ru/1.x/?ll={$lng},{$lat}&z=16&l=sat,skl&size=600,250&pt={$lng},{$lat},pm2rdm",
        );
        $this->need_space($map_h + 10);
        $ok = false;
        foreach ($map_urls as $u) {
            $ok = $this->embed_url_image($u, self::ML + 10, $this->y + 2, $map_w, $map_h);
            if ($ok) break;
        }
        if ($ok) $this->y += $map_h + 8;
    }

    $logic = $q->logic ? json_decode($q->logic, true) : array();
    if (is_array($logic)) {
        foreach ($logic as $ri => $rule) {
            if (!is_array($rule) || ($rule['action'] ?? '') !== 'add_question') continue;
            if (empty($rule['child']['label'])) continue;
            // For repeat instances, child key is prefixed: __r{n}__child_X_Y → stored in repeat_map as child_X_Y
            // For base section, child key is in child_map as child_X_Y
            $ck_base   = 'child_' . $q->id . '_' . $ri;
            $ck_prefix = $child_prefix ? $child_prefix . $ck_base : $ck_base;
            // Look in child_map first (base section), then response_map (repeat section)
            $cr = isset($child_map[$ck_base]) ? $child_map[$ck_base]
                : ( isset($response_map[$ck_base]) ? $response_map[$ck_base] : null );
            if (!$cr || trim((string)$cr->value) === '') continue;
            $this->render_child($rule['child']['label'], trim((string)$cr->value), $cw, $col_q, $col_a);
        }
    }

    $odd = !$odd;
}

    // ── Signature block ───────────────────────────────────────────

    private function render_sig($label, $val, $cw, $col_q) {
        $dec = json_decode($val, true);
        $sig_data = is_array($dec) ? ($dec['sig'] ?? '') : (strpos($val,'data:')===0?$val:'');
        $sig_name = is_array($dec) ? ($dec['name'] ?? '') : '';
        // Row height: label row (16) + image (80) + name row (18) + padding
        $img_w = 240; $img_h = 80;
        $row_h = $sig_data ? (16 + $img_h + ($sig_name ? 18 : 4) + 8) : 26;
        $this->need_space($row_h + 4);
        $this->fill_rect(self::ML, $this->y, $cw, $row_h, 249,250,251);
        // Label row
        $this->set_font('Helvetica-Bold', 9);
        $this->put_text(self::ML+6, $this->y+5, $label, 55,65,81);
        if ($val) {
            $this->set_font('Helvetica-Bold', 9);
            $this->put_text(self::ML+$col_q+5, $this->y+5, 'Signed', 22,163,74);
        }
        if ($sig_data) {
            // Signature image — centred in left portion, below label
            $img_y = $this->y + 16;
            // White bg box behind sig
            $this->fill_rect(self::ML+6, $img_y, $img_w, $img_h, 255,255,255);
            $this->draw_rect(self::ML+6, $img_y, $img_w, $img_h, 209,213,219);
            $this->embed_data_uri($sig_data, self::ML+6, $img_y, $img_w, $img_h);
            if ($sig_name) {
                // Name below image with a separator line
                $name_y = $img_y + $img_h + 4;
                $this->draw_hline(self::ML+6, $name_y, $img_w, 229,231,235);
                $this->set_font('Helvetica-Bold', 10);
                $this->put_text(self::ML+8, $name_y+5, $sig_name, 17,24,39);
            }
        }
        $this->draw_hline(self::ML, $this->y+$row_h, $cw, 225,225,225);
        $this->y += $row_h + 4;
    }

    // ── Child row ─────────────────────────────────────────────────

    private function render_child($label, $val, $cw, $col_q, $col_a) {
        $ll = $this->wrap('  '.$label, $col_q-12, 8);
        $vv = $this->wrap($val, $col_a-10, 9);
        $row_h = max(count($ll), count($vv)) * 12 + 8;
        $this->need_space($row_h);
        $this->fill_rect(self::ML, $this->y, $cw, $row_h, 245,243,255);
        $this->fill_rect(self::ML, $this->y, 3, $row_h, 139,92,246); // purple accent
        $ty = $this->y + 5;
        $this->set_font('Helvetica', 8);
        foreach ($ll as $ln) { $this->put_text(self::ML+8, $ty, $ln, 107,114,128); $ty += 12; }
        $ty = $this->y + 5;
        $this->set_font('Helvetica', 9);
        foreach ($vv as $ln) { $this->put_text(self::ML+$col_q+5, $ty, $ln, 55,65,81); $ty += 12; }
        $this->draw_hline(self::ML, $this->y+$row_h, $cw, 220,210,255);
        $this->y += $row_h + 2;
    }

    // ── Photo strip ───────────────────────────────────────────────

    private function render_photos($photos, $photo_nums = array()) {
        $sz      = 44;
        $gap     = 5;
        $cw      = self::PW - self::ML - self::MR;
        $per_row = max(1, (int)(($cw - 4) / ($sz + $gap)));
        $total   = count($photos);
        $rows    = max(1, (int)ceil($total / $per_row));
        $block_h = $rows * ($sz + 16) + 4;
        $this->need_space($block_h);

        $upload_dir = wp_upload_dir( null, false );
        $base_url   = untrailingslashit($upload_dir['baseurl']);
        $base_path  = untrailingslashit($upload_dir['basedir']);

        $row = 0; $col = 0;
        foreach ($photos as $idx => $ph) {
            $url = $ph['url'] ?? ''; if (!$url) continue;
            $x  = self::ML + 4 + $col * ($sz + $gap);
            $py = $this->y + 4 + $row * ($sz + 16);
            $clean = strtok($url,'?');
            $raw = null;
            if (strpos($clean, $base_url) === 0) {
                $fp = $base_path . substr($clean, strlen($base_url));
                if (file_exists($fp) && is_readable($fp)) $raw = file_get_contents($fp);
            }
            if ($raw) {
                $jpeg = $this->to_jpeg($raw);
                if ($jpeg) $this->embed_jpeg($jpeg, $x, $py, $sz, $sz);
            }
            $num = isset($photo_nums[$idx]) ? $photo_nums[$idx] : ($idx + 1);
            $this->set_font('Helvetica', 6);
            $this->put_text($x + 2, $py + $sz + 2, 'Photo ' . $num, 130, 130, 130);
            $col++;
            if ($col >= $per_row) { $col = 0; $row++; }
        }
        $this->y += $block_h;
    }

    // ── Image helpers ─────────────────────────────────────────────

    private function embed_data_uri($data_uri, $x, $y, $w, $h) {
        if (strpos($data_uri,'data:') !== 0) return;
        $comma = strpos($data_uri, ','); if ($comma===false) return;
        $raw = base64_decode(substr($data_uri, $comma+1));
        if (!$raw) return;
        $jpeg = $this->to_jpeg($raw);
        if ($jpeg) $this->embed_jpeg($jpeg, $x, $y, $w, $h);
    }

    private function to_jpeg($raw) {
        if (!function_exists('imagecreatefromstring')) {
            return (substr($raw,0,2)==="\xFF\xD8") ? $raw : null;
        }
        $src = @imagecreatefromstring($raw); if (!$src) return null;
        $orig_w = imagesx($src); $orig_h = imagesy($src);
        $max_dim = 1280;
        $ratio = min($max_dim / max(1,$orig_w), $max_dim / max(1,$orig_h), 1);
        $new_w = max(1, (int) round($orig_w * $ratio));
        $new_h = max(1, (int) round($orig_h * $ratio));
        $dst = imagecreatetruecolor($new_w, $new_h);
        imagefill($dst, 0, 0, imagecolorallocate($dst,255,255,255));
        imagecopyresampled($dst,$src,0,0,0,0,$new_w,$new_h,$orig_w,$orig_h);
        imagedestroy($src);
        ob_start(); imagejpeg($dst,null,58); $j=ob_get_clean(); imagedestroy($dst);
        return $j ?: null;
    }

    private function embed_jpeg($jpeg, $x, $y, $w, $h) {
        $info = @getimagesizefromstring($jpeg); if (!$info) return;
        $img_w = $info[0]; $img_h = $info[1];
        // Fit image inside box preserving aspect ratio (letterbox)
        if ($img_w > 0 && $img_h > 0) {
            $scale = min($w / $img_w, $h / $img_h);
            $fit_w = $img_w * $scale;
            $fit_h = $img_h * $scale;
            // Centre within the box
            $x = $x + ($w - $fit_w) / 2;
            $y = $y + ($h - $fit_h) / 2;
            $w = $fit_w; $h = $fit_h;
        }
        $idx = count($this->images);
        $this->images[] = array('raw'=>$jpeg,'w'=>$info[0],'h'=>$info[1]);
        $pdf_y = self::PH - $y - $h;
        $this->w(sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/Img%d Do\nQ\n",$w,$h,$x,$pdf_y,$idx));
    }

    // ── Page management ───────────────────────────────────────────

    private function new_page() {
        $this->streams[] = ''; $this->cur_page = count($this->streams)-1; $this->y = self::MT;
    }

    private function need_space($h) {
        if ($this->y + $h > self::PH - self::MB) $this->new_page();
    }

    private function w($s) { $this->streams[$this->cur_page] .= $s; }

    // ── Drawing ───────────────────────────────────────────────────

    private function fill_rect($x,$y,$w,$h,$r,$g,$b) {
        $this->w(sprintf("%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re f\n",
            $r/255,$g/255,$b/255,$x,self::PH-$y-$h,$w,$h));
    }

    private function draw_rect($x,$y,$w,$h,$r=200,$g=200,$b=200) {
        $this->w(sprintf("%.3f %.3f %.3f RG\n0.5 w\n%.2f %.2f %.2f %.2f re S\n",
            $r/255,$g/255,$b/255,$x,self::PH-$y-$h,$w,$h));
    }

    private function draw_hline($x,$y,$w,$r=200,$g=200,$b=200) {
        $this->w(sprintf("%.3f %.3f %.3f RG\n0.3 w\n%.2f %.2f m %.2f %.2f l S\n",
            $r/255,$g/255,$b/255,$x,self::PH-$y,$x+$w,self::PH-$y));
    }

    private function draw_vline($x,$y,$h) {
        $this->w(sprintf("0.85 0.85 0.85 RG\n0.3 w\n%.2f %.2f m %.2f %.2f l S\n",
            $x,self::PH-$y,$x,self::PH-$y-$h));
    }

    private function set_font($f,$s) { $this->font=$f; $this->fsize=$s; }

    private function put_text($x,$y,$txt,$r=0,$g=0,$b=0) {
        $txt = $this->san($txt); if ($txt==='') return;
        $this->w(sprintf("BT\n/%s %d Tf\n%.3f %.3f %.3f rg\n%.2f %.2f Td\n(%s) Tj\nET\n",
            $this->font,$this->fsize,$r/255,$g/255,$b/255,$x,self::PH-$y-$this->fsize,$txt));
    }

    private function put_footer($pi,$left='',$center='',$right='') {
        $left=$this->san($left);
        $center=$this->san($center);
        $right=$this->san($right);
        $fy=self::PH-self::MB+14;
        if ($left !== '') {
            $this->streams[$pi] .= sprintf("BT\n/Helvetica 7 Tf\n0.6 0.6 0.6 rg\n%.2f %.2f Td\n(%s) Tj\nET\n", self::ML, self::PH-$fy, $left);
        }
        if ($center !== '') {
            $cx = (self::PW / 2) - (strlen($center) * 1.7);
            $this->streams[$pi] .= sprintf("BT\n/Helvetica 7 Tf\n0.6 0.6 0.6 rg\n%.2f %.2f Td\n(%s) Tj\nET\n", $cx, self::PH-$fy, $center);
        }
        if ($right !== '') {
            $rx = self::PW - self::MR - max(60, strlen($right) * 3.5);
            $this->streams[$pi] .= sprintf("BT\n/Helvetica 7 Tf\n0.6 0.6 0.6 rg\n%.2f %.2f Td\n(%s) Tj\nET\n", $rx, self::PH-$fy, $right);
        }
    }


    private function draw_table_header($cw, $col_q) {
        // No Question/Answer labels — fields already show that context.
        $this->y += 2;
    }

    private function embed_url_image($url, $x, $y, $w, $h) {
        if ( empty($url) ) return false;
        $upload_dir = wp_upload_dir( null, false );
        $base_url   = untrailingslashit($upload_dir['baseurl']);
        $base_path  = untrailingslashit($upload_dir['basedir']);
        $clean      = strtok($url, '?');
        $raw = null;
        if ( strpos($clean, $base_url) === 0 ) {
            $fp = $base_path . substr($clean, strlen($base_url));
            if ( file_exists($fp) && is_readable($fp) ) $raw = file_get_contents($fp);
        }
        if ( ! $raw ) {
            $resp = wp_remote_get($url, array('timeout'=>15,'sslverify'=>false));
            if ( ! is_wp_error($resp) && (int) wp_remote_retrieve_response_code($resp) === 200 ) {
                $raw = wp_remote_retrieve_body($resp);
            }
        }
        if ( ! $raw ) return false;
        $jpeg = $this->to_jpeg($raw);
        if ( ! $jpeg ) return false;
        $this->embed_jpeg($jpeg, $x, $y, $w, $h);
        return true;
    }

    private function render_media_summary($photos, $start_num = 1) {
        $cw    = self::PW - self::ML - self::MR;
        $colGap = 12;   // horizontal gap between columns
        $rowGap = 10;   // vertical gap between rows
        $capH  = 22;    // caption height below each cell
        $cellW = (int) floor(($cw - $colGap) / 2);

        $first_page = true;
        $photo_num  = $start_num;
        $i          = 0;

        while ( $i < count($photos) ) {
            // ── New page + header ──────────────────────────────────
            $this->new_page();
            $title = $first_page ? 'Media Summary' : 'Media Summary (continued)';
            $first_page = false;
            $this->set_font('Helvetica-Bold', 14);
            $this->put_text(self::ML, self::MT + 4, $title, 17,24,39);
            $this->y = self::MT + 20;
            $this->draw_hline(self::ML, $this->y, $cw, 220,220,220);
            $this->y += 10;

            // ── Calculate cell height so exactly 2 rows fill the page ──
            $usable_h = self::PH - self::MB - $this->y;
            // 2 rows: 2*cellH + 2*capH + 1*rowGap (gap only between rows, not after last)
            $cellH = (int) floor(($usable_h - 2 * $capH - $rowGap) / 2);
            $cellH = max(80, $cellH); // floor at 80pt

            $totalCellH = $cellH + $capH + $rowGap;

            // Always 4 per page (2×2), except last page
            $photos_this_page = min(count($photos) - $i, 4);

            for ( $pi = 0; $pi < $photos_this_page; $pi++ ) {
                $ph  = $photos[$i + $pi];
                $col = $pi % 2;
                $row = (int) floor($pi / 2);
                $x   = self::ML + ($col * ($cellW + $colGap));
                $y   = $this->y + ($row * $totalCellH);

                // Photo cell background + border
                $this->fill_rect($x, $y, $cellW, $cellH, 249,250,251);
                $this->draw_rect($x, $y, $cellW, $cellH, 220,220,220);

                // Image — letterbox fit with small inset
                $this->embed_url_image($ph['url'], $x + 4, $y + 4, $cellW - 8, $cellH - 8);

                // Caption
                $cap_y = $y + $cellH + 5;
                $this->set_font('Helvetica-Bold', 8);
                $this->put_text($x + 4, $cap_y, 'Photo ' . (isset($ph['num']) ? $ph['num'] : $photo_num), 55,65,81);
                $this->set_font('Helvetica', 7);
                $this->put_text($x + 4, $cap_y + 10, ($ph['section'] ?? '') . ' - ' . ($ph['label'] ?? ''), 107,114,128);

                $photo_num++;
            }

            $i += $photos_this_page;
        }
    }


    private static function format_token_value_by_type( $raw, $type ) {
        if ( $raw === null || $raw === '' ) return '';
        $type = (string) $type;
        try {
            if ( in_array( $type, array('datetime','date_time'), true ) ) {
                return (new DateTime((string)$raw))->format('d M Y, g:i A');
            }
            if ( $type === 'date' ) {
                return (new DateTime((string)$raw))->format('d M Y');
            }
            if ( $type === 'time' ) {
                return (new DateTime('1970-01-01 '.trim((string)$raw)))->format('g:i A');
            }
        } catch ( Exception $e ) {
            return is_scalar($raw) ? (string) $raw : '';
        }
        return is_scalar($raw) ? (string) $raw : '';
    }

    private function resolve_footer_tokens( $text, $page, $pages ) {
        $text = (string) $text;
        $template = $this->inspection ? ($this->inspection->template_title ?: $this->inspection->title) : '';
        $site = $this->inspection->site_name ?? '';
        $inspector = $this->inspection->inspector_name ?? '';
        $score = isset($this->inspection->score) && $this->inspection->score !== null ? 'Score '.round($this->inspection->score).'%' : '';
        return strtr( $text, array(
            '{date}' => $this->report_date ?: date('d M Y'),
            '{template}' => $template,
            '{site}' => $site,
            '{inspector}' => $inspector,
            '{score}' => $score,
            '{page}' => (string) $page,
            '{pages}' => (string) $pages,
        ) );
    }

    private function is_dark($hex) {
        list($r,$g,$b) = $this->hex2rgb($hex);
        return (0.299*$r + 0.587*$g + 0.114*$b) < 140;
    }

    // ── Text wrap ─────────────────────────────────────────────────

    private function wrap($text,$max_w,$fs) {
        $cw = $fs * 0.50;
        $mc = max(8,(int)($max_w/$cw));
        // Split on explicit newlines first, then word-wrap each segment
        $paragraphs = preg_split('/\r?\n/', (string)$text);
        $lines = array();
        foreach ($paragraphs as $para) {
            $words = preg_split('/[ \t]+/', trim($para));
            $cur = '';
            foreach ($words as $w) {
                if ($w === '') continue;
                $test = $cur === '' ? $w : $cur.' '.$w;
                if (mb_strlen($test) > $mc && $cur !== '') {
                    $lines[] = $cur; $cur = $w;
                } else {
                    $cur = $test;
                }
            }
            if ($cur !== '') $lines[] = $cur;
            elseif (empty($words) || (count($words)===1 && $words[0]==='')) $lines[] = ''; // blank line
        }
        return $lines ?: array('');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function san($t) {
        $t = wp_strip_all_tags((string)$t);
        // Convert smart/curly quotes and common Unicode punctuation to ASCII equivalents
        $t = str_replace(
            array("â","â","â","â",
                  "â","â","â¦"),
            array("'",           "'",           '"',           '"',
                  '-',           '--',          '...'),
            $t
        );
        $t = preg_replace('/[^ -~]/',' ',$t);
        return str_replace(array('\\','(',')'),array('\\\\','\\(','\\)'),$t);
    }

    private function hex2rgb($hex) {
        $hex=ltrim($hex,'#');
        if (strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return array(hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2)));
    }

    private function text_width($text, $size) {
        // Approximate: avg char width ~0.55 * font size for Helvetica
        return mb_strlen((string)$text) * $size * 0.55;
    }

    private function ans_color($vl) {
        if ($vl==='yes')  return array(22,163,74);
        if ($vl==='no')   return array(220,38,38);
        if ($vl==='n/a')  return array(107,114,128);
        return array(17,24,39);
    }

    // ── PDF binary output ─────────────────────────────────────────

    private function render_pdf() {
        $out='%PDF-1.4\n'; $off=array(); $oid=1;

        $cat=$oid++; $pgs=$oid++; $fR=$oid++; $fB=$oid++;
        $pc=count($this->streams);
        $pids=array(); $sids=array();
        for($i=0;$i<$pc;$i++){$pids[]=$oid++;$sids[]=$oid++;}
        $iids=array();
        foreach($this->images as $_){$iids[]=$oid++;}

        $obs=array();
        $obs[$fR]="<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n/Encoding /WinAnsiEncoding\n>>";
        $obs[$fB]="<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Bold\n/Encoding /WinAnsiEncoding\n>>";

        foreach($this->images as $ii=>$img){
            $obs[$iids[$ii]]="<<\n/Type /XObject\n/Subtype /Image\n/Width ".$img['w']."\n/Height ".$img['h']
                ."\n/ColorSpace /DeviceRGB\n/BitsPerComponent 8\n/Filter /DCTDecode\n/Length ".strlen($img['raw'])
                ."\n>>\nstream\n".$img['raw']."\nendstream";
        }

        $xo=''; foreach($iids as $ii=>$id){$xo.="    /Img{$ii} {$id} 0 R\n";}
        $xres=$xo?"  /XObject <<\n{$xo}  >>\n":'';

        for($i=0;$i<$pc;$i++){
            $s=$this->streams[$i];
            $obs[$sids[$i]]="<<\n/Length ".strlen($s)."\n>>\nstream\n".$s."\nendstream";
        }
        for($i=0;$i<$pc;$i++){
            $obs[$pids[$i]]=sprintf(
                "<<\n/Type /Page\n/Parent %d 0 R\n/MediaBox [0 0 %.2f %.2f]\n/Contents %d 0 R\n/Resources <<\n  /Font <<\n    /Helvetica %d 0 R\n    /Helvetica-Bold %d 0 R\n  >>\n%s>>\n>>",
                $pgs,self::PW,self::PH,$sids[$i],$fR,$fB,$xres);
        }
        $kids=implode(' 0 R ',$pids).' 0 R';
        $obs[$pgs]="<<\n/Type /Pages\n/Kids [$kids]\n/Count $pc\n>>";
        $obs[$cat]="<<\n/Type /Catalog\n/Pages $pgs 0 R\n>>";

        $all=array_merge(array($cat,$pgs,$fR,$fB),$pids,$sids,$iids);
        sort($all);
        foreach($all as $id){$off[$id]=strlen($out);$out.="$id 0 obj\n".$obs[$id]."\nendobj\n";}

        $xref=strlen($out);
        $total=max($all)+1;
        $out.="xref\n0 $total\n";
        $out.="0000000000 65535 f \n";
        for($i=1;$i<$total;$i++){
            $out.=isset($off[$i])?sprintf("%010d 00000 n \n",$off[$i]):"0000000000 65535 f \n";
        }
        $out.="trailer\n<<\n/Size $total\n/Root $cat 0 R\n>>\nstartxref\n$xref\n%%EOF\n";
        return $out;
    }
}
