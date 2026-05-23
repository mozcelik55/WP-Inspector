<?php
/**
 * WPI_PDF – SafetyCulture-style inspection report
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_PDF {

    private static function cfg_on( $cfg, $key, $default = false ) {
        if ( ! is_array($cfg) || ! array_key_exists($key, $cfg) ) return $default;
        $v = $cfg[$key];
        if ( is_bool($v) ) return $v;
        if ( is_numeric($v) ) return ((int)$v) === 1;
        $v = strtolower(trim((string)$v));
        if ( in_array($v, array('1','true','yes','on'), true) ) return true;
        if ( in_array($v, array('0','false','no','off',''), true) ) return false;
        return (bool) $v;
    }


    private static function resize_and_encode( $path, $max_w = 600, $max_h = 450, $quality = 65 ) {
        if ( ! function_exists('imagecreatefromjpeg') ) {
            $data = file_get_contents($path);
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp');
            $ct   = isset($mime[$ext]) ? $mime[$ext] : 'image/jpeg';
            return 'data:'.$ct.';base64,'.base64_encode($data);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $src = null;
        switch ($ext) {
            case 'jpg': case 'jpeg': $src = @imagecreatefromjpeg($path); break;
            case 'png':              $src = @imagecreatefrompng($path);  break;
            case 'gif':              $src = @imagecreatefromgif($path);  break;
            case 'webp': if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path); break;
        }
        if ( !$src ) { $data = file_get_contents($path); return 'data:image/jpeg;base64,'.base64_encode($data); }
        $orig_w = imagesx($src); $orig_h = imagesy($src);
        $ratio = min($max_w / $orig_w, $max_h / $orig_h, 1.0);
        $new_w = (int)round($orig_w * $ratio); $new_h = (int)round($orig_h * $ratio);
        $dst = imagecreatetruecolor($new_w, $new_h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
        imagedestroy($src);
        ob_start(); imagejpeg($dst, null, $quality); $jpeg_data = ob_get_clean(); imagedestroy($dst);
        return 'data:image/jpeg;base64,'.base64_encode($jpeg_data);
    }

    private static function embed_image( $url, $max_w = 600, $max_h = 450, $quality = 65 ) {
        if ( !$url ) return '';
        if ( strpos($url, 'data:') === 0 ) return $url;
        $upload_dir = wp_upload_dir( null, false );
        $base_url   = untrailingslashit($upload_dir['baseurl']);
        $base_path  = untrailingslashit($upload_dir['basedir']);
        $clean_url  = strtok($url, '?');
        foreach ( array($clean_url, str_replace('https://','http://',$clean_url), str_replace('http://','https://',$clean_url)) as $try_url ) {
            foreach ( array($base_url, str_replace('https://','http://',$base_url), str_replace('http://','https://',$base_url)) as $try_base ) {
                if ( strpos($try_url, $try_base) === 0 ) {
                    $path = $base_path . substr($try_url, strlen($try_base));
                    if ( file_exists($path) && is_readable($path) ) return self::resize_and_encode($path, $max_w, $max_h, $quality);
                }
            }
        }
        $response = wp_remote_get($url, array('timeout'=>20,'sslverify'=>true));
        if ( !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ) {
            $body = wp_remote_retrieve_body($response);
            $content_type = strtolower((string) wp_remote_retrieve_header($response, 'content-type'));
            $ext = '.jpg';
            if (strpos($content_type, 'png') !== false) $ext = '.png';
            elseif (strpos($content_type, 'gif') !== false) $ext = '.gif';
            elseif (strpos($content_type, 'webp') !== false) $ext = '.webp';
            if ($body) {
                $tmp = tempnam(sys_get_temp_dir(), 'wpi_');
                $tmp_with_ext = $tmp . $ext;
                @rename($tmp, $tmp_with_ext);
                file_put_contents($tmp_with_ext, $body);
                $r = self::resize_and_encode($tmp_with_ext, $max_w, $max_h, $quality);
                @unlink($tmp_with_ext);
                return $r;
            }
        }
        return $url;
    }

    private static function embed_logo( $url ) { return self::embed_image($url, 300, 120, 85); }

    private static function hex_to_rgb( $hex ) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
    }

    private static function is_dark( $hex ) {
        list($r,$g,$b) = self::hex_to_rgb($hex);
        return (0.299*$r + 0.587*$g + 0.114*$b) < 140;
    }

    private static function get_page_layout( $page_margin = 'normal' ) {
        $pm = is_string($page_margin) ? strtolower(trim($page_margin)) : 'normal';
        switch ( $pm ) {
            case 'narrow':
                $layout = array( 'top'=>8, 'right'=>8, 'bottom'=>12, 'left'=>8 );
                break;
            case 'wide':
                $layout = array( 'top'=>20, 'right'=>18, 'bottom'=>20, 'left'=>18 );
                break;
            default:
                $layout = array( 'top'=>14, 'right'=>12, 'bottom'=>14, 'left'=>12 );
                break;
        }
        $layout['footer_height'] = 12;
        $layout['footer_gap']    = 2;
        $layout['print_bottom']  = $layout['bottom'] + $layout['footer_height'] + $layout['footer_gap'];
        $layout['css']           = sprintf('%dmm %dmm %dmm %dmm', $layout['top'], $layout['right'], $layout['print_bottom'], $layout['left']);
        $layout['footer_pad']    = sprintf('2.5mm %dmm 0 %dmm', $layout['right'], $layout['left']);
        $layout['content_gap']   = sprintf('%dmm', $layout['footer_height'] + $layout['footer_gap']);
        return $layout;
    }

    private static function resolve_wkhtml_footer_tokens( $text, $inspection, $site, $date_str ) {
        $resolved = self::resolve_footer_tokens( $text, $inspection, $site, $date_str );
        if ( $resolved === '' ) return '';
        return strtr( $resolved, array(
            '1 of 1' => '[page] of [topage]',
            'Page 1 of 1' => 'Page [page] of [topage]',
            ' 1 ' => ' [page] ',
            '{page}'  => '[page]',
            '{pages}' => '[topage]',
        ) );
    }

    public function generate( $inspection_id ) {
        // Capture any accidental output (warnings, notices) so they don't
        // corrupt the PDF binary stream or trigger "headers already sent".
        while ( ob_get_level() ) ob_end_clean();
        ob_start();
        $prev_error_reporting = error_reporting( E_ERROR ); // suppress warnings/notices during PDF gen

        global $wpdb;
        $inspection = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*,
                    t.title as template_title, t.settings as t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.id=%d", $inspection_id
        ));
        if ( !$inspection ) { status_header(404); wp_die('Inspection not found.'); }
        // site_name is stored directly on the inspection row
        if ( !$inspection ) { status_header(404); wp_die('Not found.'); }

        // ── Config ───────────────────────────────────────────────────────
        $cfg = array(
            // Visibility toggles
            'show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,
            'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,
            'show_date'=>false,'show_site'=>false,'show_gallery'=>true,
            'show_section_scores'=>false,'show_audit_title'=>true,'show_flagged_summary'=>false,
            // Branding
            'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c',
            'logo_url'=>'','logo_position'=>'left',
            'report_title'=>'',
            // Footer
            'footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}',
            'footer_text'=>'',
            // Layout
            'page_margin'=>'normal',
            // Filename
            'pdf_filename'=>'{template}/{site}/{date}',
        );
        $t_cfg = $inspection->t_settings ? json_decode($inspection->t_settings, true) : array();
        if ( is_array($t_cfg) ) $cfg = array_merge($cfg, $t_cfg);

        // ── Questions + Responses ────────────────────────────────────────
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d ORDER BY sort_order", $inspection->template_id
        ));
        // If template was deleted (no questions), reconstruct from saved responses
        if ( empty($questions) ) {
            $saved_resp = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT question_id FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d ORDER BY id",
                absint( $inspection_id )
            ));
            // Try to recover question labels from wpi_actions (stored when actions were assigned)
            $action_labels = array();
            $action_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT question_id, question_label FROM {$wpdb->prefix}wpi_actions WHERE inspection_id=%d AND question_label != ''",
                absint( $inspection_id )
            ));
            foreach ( $action_rows as $ar ) {
                $action_labels[(int)$ar->question_id] = $ar->question_label;
            }
            $fake_sort = 0;
            foreach ( $saved_resp as $sr ) {
                $qid = $sr->question_id;
                if ( ! is_numeric($qid) ) continue; // skip child/repeat keys
                $fq = new stdClass();
                $fq->id             = (int)$qid;
                $fq->label          = isset($action_labels[(int)$qid]) ? $action_labels[(int)$qid] : 'Response Field '.($fake_sort+1);
                $fq->type           = 'short_text';
                $fq->section        = 'Inspection Responses';
                $fq->sort_order     = $fake_sort++;
                $fq->is_required    = 0;
                $fq->is_scored      = 0;
                $fq->passing_answer = '';
                $fq->options        = null;
                $fq->logic          = null;
                $fq->repeatable     = 0;
                $questions[]        = $fq;
            }
        }
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d", $inspection_id
        ));

        // Build response maps
        $rmap = array(); $child_rmap = array();
        foreach ($responses as $r) {
            $raw = $r->question_id;
            if ( is_numeric($raw) ) { $rmap[(int)$raw] = $r; }
            else {
                $ph = $r->photos ? json_decode($r->photos, true) : array();
                $r->photos = is_array($ph) ? array_values(array_filter(array_map(function($x){
                    return is_array($x) && !empty($x['url']) ? $x : null;
                }, $ph))) : array();
                $child_rmap[$raw] = $r;
            }
        }

        // Build items array
        $items = array();
        foreach ($questions as $q) {
            $r  = isset($rmap[$q->id]) ? $rmap[$q->id] : null;
            $ph = $r && $r->photos ? json_decode($r->photos, true) : array();
            $ph = is_array($ph) ? array_values(array_filter(array_map(function($x){ return is_array($x)&&!empty($x['url'])?$x:null; },$ph))) : array();
            
            $logic = $q->logic ? json_decode($q->logic, true) : array();
            $items[] = (object)array(
                'id'=>$q->id,'label'=>$q->label,'type'=>$q->type,'section'=>$q->section?:'General',
                'value'=>$r?(string)$r->value:'','notes'=>$r?(string)$r->notes:'',
                'flagged'=>$r?(bool)$r->flagged:false,'photos'=>$ph,
                'logic'=>is_array($logic)?$logic:array(),
                'is_scored'=>$q->is_scored,'passing_answer'=>$q->passing_answer??'',
                'options'=>is_array(json_decode($q->options??'',true))?json_decode($q->options,true):(is_array($q->options??null)?$q->options:array()),
                'yes_no_colors'=>is_array(json_decode($q->yes_no_colors??'',true))?json_decode($q->yes_no_colors,true):array(),
            );
        }

        // Build repeat_rmap: __r{n}__{qid} → response (for repeatable section instances)
        // Also find max repeat index per section
        $repeat_rmap = array(); // [ repeatIdx => [ qid => $r ] ]
        $sec_max_repeat = array(); // [ sectionName => maxRepeatIdx ]
        // Build qid→section map for quick lookup
        $qid_to_section = array();
        foreach ($questions as $q) { $qid_to_section[$q->id] = $q->section ?: 'General'; }
        foreach ($responses as $r) {
            $raw = $r->question_id;
            if ( preg_match('/^__r(\d+)__(.+)$/', $raw, $m) ) {
                $ri  = (int)$m[1];
                $qid = $m[2];
                if ( !isset($repeat_rmap[$ri]) ) $repeat_rmap[$ri] = array();
                $ph = is_array($r->photos) ? $r->photos : ($r->photos ? json_decode($r->photos, true) : array());
                $r->photos = is_array($ph) ? array_values(array_filter(array_map(function($x){
                    return is_array($x) && !empty($x['url']) ? $x : null;
                }, $ph))) : array();
                $repeat_rmap[$ri][$qid] = $r;
                // Track max repeat index per section
                if ( isset($qid_to_section[$qid]) ) {
                    $sec = $qid_to_section[$qid];
                    if ( !isset($sec_max_repeat[$sec]) || $ri > $sec_max_repeat[$sec] ) {
                        $sec_max_repeat[$sec] = $ri;
                    }
                }
            }
        }
        ksort($repeat_rmap);

        // Guard: empty items — output a simple info page
        if ( empty($items) ) {
            while ( ob_get_level() ) ob_end_clean();
            header('Content-Type: text/html; charset=UTF-8');            $ins_title = esc_html($inspection->template_title ?: 'Inspection #'.(int)$inspection_id);
            $ins_date  = $inspection->conducted_at ? date('d M Y', strtotime($inspection->conducted_at)) : '';
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Inspection Report</title>
            <style>body{font-family:-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;}
            .box{text-align:center;padding:40px;max-width:480px;}
            .icon{font-size:56px;margin-bottom:12px;}h1{font-size:20px;color:#1a3a5c;margin-bottom:8px;}
            p{color:#64748b;font-size:14px;line-height:1.6;}</style></head>
            <body><div class="box"><div class="icon">📋</div>
            <h1>'.esc_html($ins_title).'</h1>
            '.($ins_date?'<p>Conducted: '.$ins_date.'</p>':'').'
            <p>The template for this inspection has been deleted and no response data was found.<br>The inspection record is preserved in the system.</p>
            </div></body></html>';
            exit;
        }

        // Group into sections — preserve order from sort_order (first encounter wins)
        $sections = array();
        $section_order = array(); // track insertion order explicitly
        foreach ($items as $item) {
            if ( $item->type==='signature' && empty($cfg['show_signature']) ) continue;
            $sec = $item->section ?: 'General';
            if ( !isset($sections[$sec]) ) {
                $sections[$sec] = array();
                $section_order[] = $sec;
            }
            $sections[$sec][] = $item;
        }

        // Inject repeated section instances immediately after their base section
        // so the PDF order matches the form order.
        // Build a map: base_sec → [ ri => items[] ] for all repeat indices
        $repeats_by_base = array(); // [ base_sec => [ ri => [ item, ... ] ] ]
        $rep_display_num  = array(); // [ base_sec => sequential display counter ]
        foreach ($repeat_rmap as $ri => $qmap) {
            // Determine which base sections appear at this repeat index
            $rep_sections_at_ri = array();
            foreach ( $qmap as $qid => $dummy ) {
                if ( isset($qid_to_section[$qid]) ) {
                    $rep_sections_at_ri[ $qid_to_section[$qid] ] = true;
                }
            }
            foreach ( $rep_sections_at_ri as $base_sec => $_ ) {
                // Check if THIS section's questions have actual data at this repeat index
                // Include child responses (child_X_Y keys) in the data check
                $sec_has_data = false;
                foreach ( $questions as $q ) {
                    if ( ($q->section ?: 'General') !== $base_sec ) continue;
                    $qid = (string)$q->id;
                    if ( isset($qmap[$qid]) ) {
                        $rr = $qmap[$qid];
                        $v  = isset($rr->value) ? (string)$rr->value : '';
                        if ( $v !== '' || ! empty($rr->notes) || ! empty($rr->photos) ) {
                            $sec_has_data = true;
                            break;
                        }
                    }
                    // Also check if any child questions for this parent have data
                    foreach ( $qmap as $child_key => $child_r ) {
                        if ( strpos((string)$child_key, 'child_' . $qid . '_') === 0 ) {
                            $cv = isset($child_r->value) ? (string)$child_r->value : '';
                            if ( $cv !== '' || ! empty($child_r->notes) || ! empty($child_r->photos) ) {
                                $sec_has_data = true;
                                break 2;
                            }
                        }
                    }
                }
                if ( ! $sec_has_data ) continue; // skip empty repeat for this section

                if ( !isset($repeats_by_base[$base_sec]) ) $repeats_by_base[$base_sec] = array();
                if ( !isset($rep_display_num[$base_sec]) ) $rep_display_num[$base_sec] = 1;
                $rep_display_num[$base_sec]++;
                $rep_sec = $base_sec . ' #' . $rep_display_num[$base_sec];
                // Build a child_rmap for this repeat instance by stripping __r{ri}__ prefix
                // e.g. "__r1__child_123_0" → "child_123_0"
                $rep_prefix   = '__r' . $ri . '__';
                $rep_child_rmap = array();
                foreach ( $child_rmap as $ck => $cv ) {
                    if ( strpos($ck, $rep_prefix) === 0 ) {
                        $stripped = substr($ck, strlen($rep_prefix));
                        $rep_child_rmap[$stripped] = $cv;
                    }
                }
                // ALSO check repeat_rmap for child_ entries at this index
                // repeat_rmap[$ri]['child_123_0'] may exist if stored as __r{ri}__child_123_0
                if ( isset($repeat_rmap[$ri]) ) {
                    foreach ( $repeat_rmap[$ri] as $rk => $rv ) {
                        if ( strpos((string)$rk, 'child_') === 0 ) {
                            $rep_child_rmap[$rk] = $rv;
                        }
                    }
                }

                $rep_items = array();
                foreach ( $questions as $q ) {
                    if ( $q->type === 'page' ) continue; // page breaks don't repeat
                    if ( $q->type==='signature' && empty($cfg['show_signature']) ) continue;
                    if ( ($q->section ?: 'General') !== $base_sec ) continue;
                    $qid   = (string)$q->id;
                    $r     = isset($qmap[$qid]) ? $qmap[$qid] : null;
                    $ph    = $r && $r->photos ? ( is_array($r->photos) ? $r->photos : json_decode($r->photos,true) ) : array();
                    $ph    = is_array($ph) ? array_values(array_filter(array_map(function($x){ return is_array($x)&&!empty($x['url'])?$x:null; },$ph))) : array();
                    $logic = $q->logic ? json_decode($q->logic, true) : array();
                    $rep_items[] = (object)array(
                        'id'             => $q->id,
                        'label'          => $q->label,
                        'type'           => $q->type,
                        'section'        => $rep_sec,
                        'value'          => $r ? (string)$r->value : '',
                        'notes'          => $r ? (string)$r->notes : '',
                        'flagged'        => $r ? (bool)$r->flagged : false,
                        'photos'         => $ph,
                        'logic'          => is_array($logic) ? $logic : array(),
                        'is_scored'      => $q->is_scored,
                        'passing_answer' => $q->passing_answer ?? '',
                        'options'        => is_array(json_decode($q->options??'',true))?json_decode($q->options,true):(is_array($q->options??null)?$q->options:array()),
                        'yes_no_colors'  => is_array(json_decode($q->yes_no_colors??'',true))?json_decode($q->yes_no_colors,true):array(),
                        '_child_rmap'    => $rep_child_rmap, // pass repeat-specific child map
                    );
                }
                $sections[$rep_sec] = $rep_items;
                $repeats_by_base[$base_sec][$ri] = $rep_sec;
            }
        }

        // Rebuild section_order inserting repeat instances right after their base section
        $new_section_order = array();
        foreach ( $section_order as $sec ) {
            $new_section_order[] = $sec;
            if ( isset($repeats_by_base[$sec]) ) {
                ksort($repeats_by_base[$sec]);
                foreach ( $repeats_by_base[$sec] as $ri => $rep_sec ) {
                    $new_section_order[] = $rep_sec;
                }
            }
        }
        $section_order = $new_section_order;



        // ── Apply section show/hide conditions before building report rows ─────
        // Section conditions are stored in template settings as:
        // section_conditions = { "Section name": { question_id, question_key, question_db_id, value, mode } }
        // For the report, hidden sections must be completely removed, including unanswered rows.
        $wpi_get_resp_value = function($ref) use ($rmap) {
            $ref = (string)$ref;
            if ($ref === '') return '';
            if (is_numeric($ref) && isset($rmap[(int)$ref])) return (string)$rmap[(int)$ref]->value;
            foreach ($rmap as $qid => $rr) {
                if ((string)$qid === $ref) return (string)$rr->value;
            }
            return '';
        };
        $wpi_values_equal = function($actual, $expected) {
            return trim((string)$actual) === trim((string)$expected);
        };
        $wpi_rule_matches = function($actual, $when) {
            $actual = trim((string)$actual);
            $when   = is_array($when) ? (string)($when['label'] ?? $when['value'] ?? '') : (string)$when;
            if ($when === 'any') return true;
            if ($when === 'answered') return $actual !== '';
            if ($when === 'empty') return $actual === '';
            return $actual === trim($when);
        };
        $wpi_logic_section_vis = array();
        foreach ($items as $it) {
            if (empty($it->logic) || !is_array($it->logic)) continue;
            foreach ($it->logic as $rule) {
                if (empty($rule['section']) || empty($rule['action'])) continue;
                if ($rule['action'] !== 'show_section' && $rule['action'] !== 'hide_section') continue;
                $target = (string)$rule['section'];
                if (!isset($wpi_logic_section_vis[$target])) {
                    $wpi_logic_section_vis[$target] = array('hasShow'=>false,'showMatched'=>false,'hideMatched'=>false);
                }
                $matched = $wpi_rule_matches($it->value ?? '', $rule['when'] ?? '');
                if ($rule['action'] === 'show_section') {
                    $wpi_logic_section_vis[$target]['hasShow'] = true;
                    if ($matched) $wpi_logic_section_vis[$target]['showMatched'] = true;
                }
                if ($rule['action'] === 'hide_section' && $matched) {
                    $wpi_logic_section_vis[$target]['hideMatched'] = true;
                }
            }
        }
        $wpi_should_show_section = function($sec_name) use ($cfg, $wpi_get_resp_value, $wpi_values_equal, $wpi_logic_section_vis) {
            $base_sec = preg_replace('/\s+#\d+$/', '', (string)$sec_name);
            $conds = isset($cfg['section_conditions']) && is_array($cfg['section_conditions']) ? $cfg['section_conditions'] : array();
            $cond = $conds[$sec_name] ?? ($conds[$base_sec] ?? null);
            if (is_array($cond) && (!empty($cond['question_db_id']) || !empty($cond['question_id']) || !empty($cond['question_key'])) && isset($cond['value']) && $cond['value'] !== '') {
                $refs = array($cond['question_db_id'] ?? '', $cond['question_id'] ?? '', $cond['question_key'] ?? '');
                $cval = '';
                foreach ($refs as $ref) {
                    if ($ref === '') continue;
                    $cval = $wpi_get_resp_value($ref);
                    if ($cval !== '') break;
                }
                $match = $wpi_values_equal($cval, $cond['value']);
                $mode  = isset($cond['mode']) ? (string)$cond['mode'] : 'show';
                if ($mode === 'hide') { if ($match) return false; }
                else { if (!$match) return false; }
            }
            $v = $wpi_logic_section_vis[$sec_name] ?? ($wpi_logic_section_vis[$base_sec] ?? null);
            if (is_array($v)) {
                if (!empty($v['hideMatched'])) return false;
                if (!empty($v['hasShow']) && empty($v['showMatched'])) return false;
            }
            return true;
        };
        $section_order = array_values(array_filter($section_order, function($sec_name) use ($wpi_should_show_section, &$sections) {
            if (!$wpi_should_show_section($sec_name)) { unset($sections[$sec_name]); return false; }
            return true;
        }));
        $items = array_values(array_filter($items, function($it) use ($wpi_should_show_section) {
            return $wpi_should_show_section($it->section ?: 'General');
        }));

        // ── Collect all photos in section_order (includes repeated sections) ──
        $all_photos = array();
        foreach ($section_order as $sec_name) {
            if ( !isset($sections[$sec_name]) ) continue;
            foreach ($sections[$sec_name] as $item) {
                foreach ($item->photos as $ph) {
                    $url = $ph['url'] ?? '';
                    if ( $url ) $all_photos[] = array('url'=>$url,'label'=>$item->label,'section'=>$sec_name);
                }
                foreach (self::resolve_children($item, (isset($item->_child_rmap)&&is_array($item->_child_rmap)?$item->_child_rmap:$child_rmap), 0) as $child) {
                    foreach ($child->photos as $ph) {
                        $url = $ph['url'] ?? '';
                        if ( $url ) $all_photos[] = array('url'=>$url,'label'=>$child->label,'section'=>$sec_name);
                    }
                }
            }
        }

        // Embed images (thumbnail for inline, full for gallery)
        $embedded = array(); $embedded_full = array();
        foreach ($all_photos as $ph) {
            $url = $ph['url'];
            if (!isset($embedded[$url]))      $embedded[$url]      = self::embed_image($url, 160, 120, 60);
            if (!isset($embedded_full[$url])) $embedded_full[$url] = self::embed_image($url, 600, 450, 65);
        }

        // ── Stats ────────────────────────────────────────────────────────
        $yes_count  = count(array_filter($items,function($i){return strtolower($i->value)==='yes';}));
        $no_count   = count(array_filter($items,function($i){return strtolower($i->value)==='no';}));
        $na_count   = count(array_filter($items,function($i){return strtolower($i->value)==='n/a';}));
        $flag_count = count(array_filter($items,function($i){return $i->flagged;}));
        $total_q    = count(array_filter($items,function($i){return !in_array($i->type,array('instruction','page'));}));
        $answered_q = count(array_filter($items,function($i){ return !in_array($i->type,array('instruction','page'))&&($i->value!==''||count($i->photos)>0); }));

        $score       = $inspection->score!==null ? round((float)$inspection->score) : null;
        $score_str   = $score!==null ? 'Score '.$score.'%' : 'N/A';
        $score_color = $score===null ? '#6b7280' : ($score>=80 ? '#16a34a' : ($score>=50 ? '#d97706' : '#dc2626'));
        $score_bg    = $score===null ? '#f3f4f6' : ($score>=80 ? '#dcfce7' : ($score>=50 ? '#fef3c7' : '#fee2e2'));

        $is_complete  = strtolower($inspection->status??'') === 'complete';
        $status       = $is_complete ? 'Complete' : ucfirst($inspection->status??'In Progress');
        $status_color = $is_complete ? '#16a34a' : '#d97706';
        // Timezone — use WP setting, fall back to UTC
        // Timezone from WP settings
        $wp_tz_string = get_option('timezone_string') ?: '';
        if ( !$wp_tz_string ) {
            $offset = (float) get_option('gmt_offset', 0);
            $sign   = $offset >= 0 ? '+' : '-';
            $abs    = abs($offset);
            $h      = (int)$abs;
            $m      = (int)(($abs - $h) * 60);
            $wp_tz_string = sprintf('UTC%s%02d:%02d', $sign, $h, $m);
        }
        try { $wp_tz = new DateTimeZone($wp_tz_string); } catch(Exception $e) { $wp_tz = new DateTimeZone('UTC'); }

        // conducted_at is stored as WP local time (MySQL DATETIME, no tz suffix)
        // Parse it as a plain string — no timezone conversion needed, it's already local
        $raw_dt = $inspection->conducted_at ?? $inspection->created_at ?? '';
        $date_str = ''; $time_str = ''; $tz_abbr = '';
        if ( $raw_dt ) {
            try {
                $dt       = new DateTime($raw_dt, $wp_tz);
                $date_str = $dt->format('d M Y');
                $time_str = $dt->format('g:i A');
                // Get a human abbreviation — suppress generic 'UTC' for offset-based zones
                $tz_transitions = $wp_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
                $abbr_raw = !empty($tz_transitions[0]['abbr']) ? $tz_transitions[0]['abbr'] : '';
                $tz_abbr = ($abbr_raw && $abbr_raw !== 'UTC') ? $abbr_raw : '';
            } catch(Exception $e) {
                // Fallback: parse the raw string directly
                $parts    = preg_split('/[\s\-:T]/', $raw_dt);
                $date_str = self::format_display_datetime( $raw_dt );
                $time_str = '';
                $tz_abbr  = '';
            }
        }
        $site = $inspection->site_name ?: '';

        $header_col   = '#ffffff';
        $header_text  = '#000000';
        $accent_col   = !empty($cfg['accent_color']) ? $cfg['accent_color'] : $header_col;
        // Footer: resolve tokens for L/C/R
        $footer_left_raw   = $cfg['footer_left']   ?? '{template}';
        $footer_center_raw = $cfg['footer_center']  ?? '';
        $footer_right_raw  = $cfg['footer_right']   ?? 'Page {page} of {pages}';
        $footer_text_legacy = $cfg['footer_text']  ?: '';
        // Logo position
        $logo_pos = $cfg['logo_position'] ?? 'left';
        // Page margin / footer reserve
        $page_layout     = self::get_page_layout( $cfg['page_margin'] ?? 'normal' );
        $page_margin_css = $page_layout['css'];
        $footer_pad_css  = $page_layout['footer_pad'];
        $content_gap_css = $page_layout['content_gap'];

        // ── Resolve report_title tokens ──────────────────────────────────
        // report_title is now both the visible heading and the share/save filename.
        // iOS Safari uses <title> as the filename when sharing inline HTML.
        $raw_title_tpl = trim($cfg['report_title'] ?? '');

        // Always build field slug map — needed for both report_title and pdf_filename tokens
        $q_map_t = array();
        foreach ( $items as $item ) {
            if ( in_array($item->type, array('instruction','page')) ) continue;
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i','_', $item->label ?? ''));
            $slug = trim($slug,'_');
            $slug = substr($slug,0,40);
            if ( $slug && isset($rmap[$item->id]) ) {
                $q_map_t[$slug] = self::format_token_value_by_type( $rmap[$item->id]->value, $item->type ?? '' );
            }
        }
        // Ensure {field:site} always resolves — fall back to the inspection's site_name
        // if no form question with label "site" provided a response value.
        if ( empty($q_map_t['site']) && !empty($inspection->site_name) ) {
            $q_map_t['site'] = $inspection->site_name;
        }
        // Seed other common meta tokens so {field:conducted_on} etc. always resolve
        if ( empty($q_map_t['conducted_on']) && !empty($inspection->conducted_at) ) {
            try {
                $dt_co = new DateTime($inspection->conducted_at);
                $q_map_t['conducted_on'] = $dt_co->format('d M Y, g:i A');
            } catch(Exception $e) {}
        }
        if ( empty($q_map_t['prepared_by']) ) {
            $inspector = trim(($inspection->inspector_name ?? '') ?: ($inspection->inspector_display ?? ''));
            if ($inspector) $q_map_t['prepared_by'] = $inspector;
        }
        if ( empty($q_map_t['inspector']) ) {
            $inspector2 = trim(($inspection->inspector_name ?? '') ?: ($inspection->inspector_display ?? ''));
            if ($inspector2) $q_map_t['inspector'] = $inspector2;
        }
        if ( empty($q_map_t['audit_title']) || empty($q_map_t['audit_tittle']) ) {
            $at = $inspection->title ?? '';
            if ($at) { $q_map_t['audit_title'] = $at; $q_map_t['audit_tittle'] = $at; }
        }

        if ( $raw_title_tpl ) {
            $dummy = null; // q_map_t already built above
            $dt_t = !empty($inspection->conducted_at) ? new DateTime($inspection->conducted_at) : new DateTime();
            $res = $raw_title_tpl;
            $res = str_replace('{date}',      $dt_t->format('d M Y'),                           $res);
            $res = str_replace('{time}',      $dt_t->format('g:i A'),                           $res);
            $res = str_replace('{template}',  $inspection->template_title ?? $inspection->title, $res);
            $res = str_replace('{site}',      $inspection->site_name ?: '',                      $res);
            $res = str_replace('{inspector}', $inspection->inspector_name ?? '',                 $res);
            $score_val = $inspection->score !== null ? round($inspection->score).'%' : '';
            $res = str_replace('{score}',     $score_val,                                        $res);
            $res = preg_replace_callback('/\{field:([^}]+)\}/', function($m) use ($q_map_t) {
                $t = trim($m[1]);
                if ( isset($q_map_t[$t]) ) return $q_map_t[$t];
                foreach ( $q_map_t as $k => $v ) { if ( strpos($k,$t)===0 ) return $v; }
                return '';
            }, $res);
            $res = trim(preg_replace('/\s+/', ' ', $res));
            $doc_title = $res ?: $inspection->title;
        } else {
            $doc_title = $inspection->title;
        }
        $report_title = $doc_title; // used in header, summary, breadcrumb etc.

        // ── PDF filename = Report Title (already resolved above as $report_title) ──
        $save_name = $report_title;
        // Normalize save_name for use as filename — strip chars unsafe for filenames
        $save_name = preg_replace( '/[\x00-\x1f\x7f]/u', '', $save_name );
        $save_name = str_replace( array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '-', $save_name );
        $save_name = trim( preg_replace( '/\s+/', ' ', $save_name ) ) ?: 'Inspection Report';

        $logo_html = '';
        if ( !empty($cfg['logo_url']) ) {
            $logo_src  = self::embed_logo($cfg['logo_url']);
            $logo_align = $logo_pos === 'right' ? 'margin-left:auto;margin-right:0;'
                        : ($logo_pos === 'center' ? 'margin-left:auto;margin-right:auto;' : '');
            $logo_html = '<img src="'.esc_attr($logo_src).'" class="logo-img" style="'.esc_attr($logo_align).'">';
        }

        // ── BUILD SECTION ROWS ──────────────────────────────────────────
        // Build url → global photo number map so inline thumbnails match the Media Summary
        $photo_num_map = array();
        foreach ($all_photos as $gi => $gph) {
            $url = $gph['url'];
            if (!isset($photo_num_map[$url])) $photo_num_map[$url] = $gi + 1;
        }
        $body_html = '';
        foreach ($section_order as $sec_name) { $sec_items = $sections[$sec_name];
            $force_section_new_page = (!empty($sec_items) && isset($sec_items[0]->type) && $sec_items[0]->type === 'page');
            if ($force_section_new_page) {
                while (!empty($sec_items) && isset($sec_items[0]->type) && $sec_items[0]->type === 'page') {
                    array_shift($sec_items);
                }
            }
            $section_has_sig = !empty(array_filter($sec_items,function($i){return $i->type==='signature';}));
            if ($section_has_sig && !$force_section_new_page) { $force_section_new_page = true; }
            $section_classes = 'section' . ($force_section_new_page ? ' section-start-new-page' : '');
            $section_style = ''; // page breaks handled by CSS only
            // Section score — only show if there are scored questions with real scores
            $s_scored_total = count(array_filter($sec_items,function($i){
                return $i->is_scored!==null && !in_array($i->type,array('instruction','page','signature'));
            }));
            $s_score_str = '';
            if ($s_scored_total > 0) {
                $s_passed = count(array_filter($sec_items,function($i){
                    if ($i->is_scored===null) return false;
                    if ($i->value==='') return false;
                    if ($i->is_scored==0) return false;
                    $pa = strtolower(trim($i->passing_answer??''));
                    return $pa==='' || $pa==='any' || $pa===strtolower(trim($i->value));
                }));
                $s_score_str = round($s_passed/$s_scored_total*100).'%';            }

            $body_html .= '
            <div class="'.esc_attr($section_classes).'"'.($section_style ? ' style="'.esc_attr($section_style).'"' : '').'>
              <div class="section-header">
                <span class="section-name">'.esc_html($sec_name).'</span>
                '.((!empty($cfg['show_section_scores']) && $s_score_str) ? '<span class="section-score">'.esc_html($s_score_str).'</span>' : '').
            '</div>';

            foreach ($sec_items as $item) {
                $vl = strtolower(trim($item->value));
                $item_child_rmap = isset($item->_child_rmap) && is_array($item->_child_rmap) ? $item->_child_rmap : $child_rmap;
                $children = self::resolve_children($item, $item_child_rmap, 0);

                if ($item->type === 'page') {
                    $body_html .= '<div class="page-break template-page-break"></div>';
                    continue;
                }

                if ($item->type === 'instruction') {
                    $body_html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:4px 0;">'
                        . '<tr>'
                        . '<td bgcolor="#eff6ff" style="background-color:#eff6ff;border-left:4px solid #3b82f6;border-top:1px solid #bfdbfe;border-bottom:1px solid #bfdbfe;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">'
                        . '<div style="font-size:10px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">&#9432; INFORMATION</div>'
                        . '<div style="font-size:12px;color:#1e3a8a;line-height:1.7;">'.nl2br(esc_html($item->label)).'</div>'
                        . '</td>'
                        . '</tr>'
                        . '</table>';
                    continue;
                }

                // Build response value display
                $val_html = self::response_value($item, $vl, $embedded);

                // Flag badge
                $flag_html = $item->flagged ? '<span class="flag-pill">⚑ Flagged</span>' : '';

                // Notes — always show if content exists, auditor typed it intentionally
                $note_html = '';
                $note_required_by_logic = self::logic_requires_note($item);
                if ($item->notes) {
                    $note_class = $note_required_by_logic ? 'row-note row-note-required' : 'row-note';
                    $note_html = '<div class="'.$note_class.'" style="white-space:pre-wrap">'.nl2br(esc_html($item->notes)).'</div>';
                }

                // Photos inline (thumbnails)
                $photos_html = '';
                if (!empty($cfg['show_photos']) && count($item->photos) > 0) {
                    $cnt = count($item->photos);
                    $photos_html = '<div class="photo-strip">';
                    foreach ($item->photos as $idx => $ph) {
                        $url = $ph['url']??'';
                        if (!$url) continue;
                        $src = isset($embedded[$url]) ? $embedded[$url] : $url;
                        $pn = isset($photo_num_map[$url]) ? $photo_num_map[$url] : ($idx+1);
                        $photos_html .= '<div class="photo-thumb"><img src="'.esc_attr($src).'" alt="Photo '.$pn.'"><span class="photo-num">Photo '.$pn.'</span></div>';
                    }
                    $photos_html .= '</div>';
                }

                // Signature
                $sig_html = '';
                if ($item->type === 'signature' && $item->value) {
                    $decoded  = json_decode($item->value, true);
                    $sig_name = is_array($decoded) ? ($decoded['name']??'') : '';
                    $sig_date = $date_str.' '.$time_str.' '.$tz_abbr;
                    $sig_data = is_array($decoded) ? ($decoded['sig']??'') : (strpos($item->value,'data:')===0?$item->value:'');
                    if ($sig_data || $sig_name) {
                        $sig_html = '<div class="sig-block">';
                        if ($sig_data) $sig_html .= '<img src="'.esc_attr($sig_data).'" class="sig-img">';
                        $sig_html .= '<div class="sig-meta">';
                        if ($sig_name) $sig_html .= '<span class="sig-name">'.esc_html($sig_name).'</span>';
                        $sig_html .= '<span class="sig-date">'.esc_html($sig_date).'</span>';
                        $sig_html .= '</div></div>';
                    }
                }

                $is_long = ($item->type === 'textarea' || $item->type === 'long_text' || $item->type === 'short_text')
                           || (strlen(wp_strip_all_tags((string)$item->value)) > 70);
                $row_extra_class = ($item->type === 'signature') ? ' row-sig' : '';
                $is_chip = in_array($item->type, array('yes_no','multiple_choice','select','dropdown','checkbox','radio','multi_select'));

                // Build child rows HTML (used in both paths)
                $children_html = '';
                foreach ($children as $child) {
                    $cvl = strtolower(trim($child->value));
                    $child_val  = self::response_value($child, $cvl, $embedded);
                    $child_note = $child->notes ? '<div class="row-note" style="white-space:pre-wrap">'.nl2br(esc_html($child->notes)).'</div>' : '';
                    $child_photos = '';
                    if (!empty($cfg['show_photos']) && count($child->photos)>0) {
                        $child_photos = '<div class="photo-strip">';
                        foreach ($child->photos as $ci => $cph) {
                            $curl = $cph['url']??''; if (!$curl) continue;
                            $csrc = isset($embedded[$curl]) ? $embedded[$curl] : $curl;
                            $cpn = isset($photo_num_map[$curl]) ? $photo_num_map[$curl] : ($ci+1);
                            $child_photos .= '<div class="photo-thumb"><img src="'.esc_attr($csrc).'" alt="Photo '.$cpn.'"><span class="photo-num">Photo '.$cpn.'</span></div>';
                        }
                        $child_photos .= '</div>';
                    }
                    $depth   = isset($child->depth) ? (int)$child->depth : 1;
                    $children_html .= '
                    <div class="child-row">
                      <div class="child-label">↳ '.esc_html($child->label).'</div>
                      <div class="child-value">'.$child_val.'</div>'
                      .$child_note.$child_photos.'
                    </div>';
                }

                // Chip answers use a real HTML table row — works in all PDF renderers
                if (!$is_long && $is_chip) {
                    $chip = self::chip_data($item, $vl);
                    $chip_bg  = $chip ? esc_attr($chip['bg']) : '#e5e7eb';
                    $chip_fg  = $chip ? esc_attr($chip['fg']) : '#374151';
                    $chip_txt = $chip ? esc_html($chip['label']) : esc_html($item->value);
                    $body_html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;border-bottom:1px solid #f1f5f9;">'
                        . '<tr>'
                        . '<td style="font-size:13px;color:#1a1a2e;font-weight:700;font-family:Arial,Helvetica,sans-serif;padding:11px 8px 11px 20px;width:60%;vertical-align:middle;">'.esc_html($item->label).'</td>'
                        . '<td bgcolor="'.$chip_bg.'" style="background:'.$chip_bg.';color:'.$chip_fg.';font-size:13px;font-weight:700;padding:10px 20px;text-align:right;vertical-align:middle;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">'.$chip_txt.$flag_html.'</td>'
                        . '</tr>'
                        . ($note_html   ? '<tr><td colspan="2" style="padding:2px 20px 8px 20px;">'.$note_html.'</td></tr>' : '')
                        . ($photos_html ? '<tr><td colspan="2" style="padding:2px 20px 8px 20px;">'.$photos_html.'</td></tr>' : '')
                        . '</table>';
                    $body_html .= $children_html;
                } else {
                    $body_html .= '
                    <div class="row'.($item->flagged?' row-flagged':'').$row_extra_class.'">
                      <div class="row-label">'.esc_html($item->label).'</div>
                      '.($is_long
                        ? '<div class="row-value-block">'.$val_html.$flag_html.'</div>'
                        : '<div class="row-value">'.$val_html.$flag_html.'</div>'
                      )
                      .$note_html.$photos_html;
                    $body_html .= $children_html;
                    if ($sig_html) {
                        $body_html .= '<div class="sig-row-inner">' . $sig_html . '</div>';
                    }
                    $body_html .= '</div>'; // close .row
                }
            }

            $body_html .= '</div>'; // close .section
        }

        // ── FLAGGED SUMMARY ────────────────────────────────────────────
        $flagged_summary_html = '';
        if ( self::cfg_on($cfg, 'show_flagged_summary', false) ) {
            $flagged_items = array();
            foreach ($items as $item) {
                if ($item->flagged || ($item->value !== '' && self::cfg_on($cfg, 'show_flagged_only', false))) {
                    if ($item->flagged) $flagged_items[] = $item;
                }
            }
            if ( count($flagged_items) > 0 ) {
                $flagged_summary_html = '<div class="flagged-summary"><div class="flagged-summary-header">⚑ Flagged Items Summary (' . count($flagged_items) . ')</div>';
                foreach ($flagged_items as $fi) {
                    $fvl = strtolower(trim($fi->value));
                    $fval = self::response_value($fi, $fvl, $embedded);
                    $flagged_summary_html .= '<div class="flagged-row">'
                        . '<div class="flagged-section">' . esc_html($fi->section) . '</div>'
                        . '<div class="flagged-label">' . esc_html($fi->label) . '</div>'
                        . '<div class="flagged-value">' . $fval . '</div>'
                        . ($fi->notes ? '<div class="flagged-note" style="white-space:pre-wrap">' . nl2br(esc_html($fi->notes)) . '</div>' : '')
                        . '</div>';
                }
                $flagged_summary_html .= '</div>';
            }
        }

        // ── AUDITOR NOTES ───────────────────────────────────────────────
        $notes_html = '';
        if (!empty($inspection->notes) && self::cfg_on($cfg, 'show_notes', true)) {
            $notes_html = '<div class="auditor-notes">
              <div class="notes-header">Auditor Notes</div>
              <div class="notes-text">'.nl2br(esc_html($inspection->notes)).'</div>
            </div>';
        }

        // ── GALLERY ──────────────────────────────────────────────────────
        $gallery_html = '';
        if (!isset($show_gallery_pg)) $show_gallery_pg = self::cfg_on($cfg, 'show_gallery', true);
        if ($show_gallery_pg && count($all_photos) > 0) {
            // Gallery: 4 photos per page (2x2 grid), each chunk has its own header+logo
            $logo_in_gallery = !empty($cfg['logo_url'])
                ? '<img src="'.esc_attr(self::embed_logo($cfg['logo_url'])).'" class="logo-img" style="max-height:40px;max-width:120px;">'
                : '';
            $gallery_html = '<div class="gallery-page">';
            $img_num = 1;
            $chunks = array_chunk($all_photos, 4);
            foreach ($chunks as $ci => $chunk) {
                $title = $ci === 0 ? 'Media Summary' : 'Media Summary (continued)';
                $gallery_html .= '<div class="gallery-chunk">'
                    . '<div class="gallery-header">'
                    .   '<span class="gallery-title">'.$title.'</span>'
                    .   $logo_in_gallery
                    . '</div>'
                    . '<div class="gallery-grid">';
                foreach ($chunk as $ph) {
                    $url  = $ph['url'];
                    $fsrc = isset($embedded_full[$url]) ? $embedded_full[$url] : $url;
                    $gallery_html .= '<div class="gallery-cell">'
                        . '<div class="gallery-img-wrap">'
                        . '<img src="'.esc_attr($fsrc).'" alt="Photo '.$img_num.'" onerror="this.parentNode.innerHTML=\'<div class=gallery-err>Photo unavailable</div>\'">' 
                        . '</div>'
                        . '<div class="gallery-cap"><strong>Photo '.$img_num.'</strong></div>'
                        . '</div>';
                    $img_num++;
                }
                $gallery_html .= '</div></div>';
            }
            $gallery_html .= '</div>';
        }
        // META ROWS — respect header settings toggles
        $show_audit_title = self::cfg_on($cfg, 'show_audit_title', true);
        $show_site_hdr    = self::cfg_on($cfg, 'show_site', false);
        $show_date_hdr    = self::cfg_on($cfg, 'show_date', false);
        $show_inspector_hdr = self::cfg_on($cfg, 'show_inspector', false);
        $show_score_hdr   = self::cfg_on($cfg, 'show_score', false);
        $show_summary_bar = self::cfg_on($cfg, 'show_summary', false);
        $show_gallery_pg  = self::cfg_on($cfg, 'show_gallery', true);

        $meta_rows = array();
        if ( $inspection->template_title && $show_audit_title )
            $meta_rows[] = array('Audit Title',  $inspection->template_title);
        if ( $site && $show_site_hdr )
            $meta_rows[] = array('Site',         $site);
        if ( $show_date_hdr )
            $meta_rows[] = array('Conducted on', trim($date_str.' '.$time_str.' '.$tz_abbr));
        if ( $show_inspector_hdr ) {
            // Prefer the saved "Prepared by" field response over the WP user name
            $prepared_by_val = $q_map_t['prepared_by'] ?? $q_map_t['prepared by'] ?? '';
            if ( empty($prepared_by_val) ) $prepared_by_val = $inspection->inspector_name ?? '';
            if ( ! empty($prepared_by_val) )
                $meta_rows[] = array('Prepared by', $prepared_by_val);
        }
        if ( $show_score_hdr && $score !== null )
            $meta_rows[] = array('Score', $score_str);

        $meta_html = '';
        foreach ($meta_rows as $mr) {
            $meta_html .= '<div class="meta-row"><span class="meta-label">'.esc_html($mr[0]).':</span><span class="meta-value">'.esc_html($mr[1]).'</span></div>';
        }


        // ── SUMMARY BAR ─────────────────────────────────────────────────
        $summary_cells = array(
            array('val'=>$score_str,    'label'=>'Score',    'color'=>$score_color, 'show'=>$score!==null),
            array('val'=>$flag_count,   'label'=>'Flagged items',  'color'=>'#f59e0b',    'show'=>true),
            array('val'=>0,             'label'=>'Actions',  'color'=>'#6b7280',    'show'=>true),
        );
        $summary_html = '';
        if ( $show_summary_bar ) {
            $summary_html = '<div class="summary-bar">';
            $summary_html .= '<div class="summary-breadcrumb">'.esc_html($site ?: $report_title).' / '.esc_html($inspection->template_title).' / '.esc_html($date_str).' <span class="summary-status" style="color:'.$status_color.'">'.esc_html($status).'</span></div>';
            $summary_html .= '<div class="summary-stats">';
            foreach ($summary_cells as $sc) {
                if (!$sc['show']) continue;
                // Respect show_score toggle for the score cell
                if ($sc['label']==='Score' && !$show_score_hdr) continue;
                $summary_html .= '<div class="sum-cell"><span class="sum-num">'.esc_html($sc['val']).'</span><span class="sum-label">'.esc_html($sc['label']).'</span></div>';
            }
            $summary_html .= '</div></div>';
        }

        // ── FINAL HTML ──────────────────────────────────────────────────
        // Build JS separately so no JS code sits inside a PHP single-quoted string
        // If a pre-resolved title was passed via ?title= param, use it as the filename override
        $url_title = isset($_GET['title']) ? sanitize_textarea_field(stripslashes($_GET['title'])) : '';
        if ( $url_title ) $save_name = $url_title;
        // Sanitize save_name again after possible override
        $save_name = preg_replace( '/[\x00-\x1f\x7f]/u', '', $save_name );
        $save_name = str_replace( array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '-', $save_name );
        $save_name = trim( preg_replace( '/\s+/', ' ', $save_name ) ) ?: 'Inspection Report';

        $is_print = ! empty( $_GET['print'] );
        $doc_title_js = wp_json_encode( $save_name );
        $auto_print = $is_print
            ? 'window.addEventListener("load",function(){document.title=WPI_TITLE;setTimeout(function(){window.print();},800);});'
            : '';
        // Build the print URL — opens a clean print-ready version that auto-triggers window.print()
        // This is the most reliable way to get a pixel-perfect PDF matching the view.
        $print_url = home_url('/?wpi_pdf=1')
            . '&id='    . absint($_GET['id'] ?? 0)
            . '&nonce=' . urlencode($_GET['nonce'] ?? '')
            . '&print=1'
            . '&title=' . urlencode($save_name);
        $print_url_js    = wp_json_encode($print_url);
        $download_url = home_url('/?wpi_pdf=1')
            . '&id='    . absint($_GET['id'] ?? 0)
            . '&nonce=' . urlencode($_GET['nonce'] ?? '')
            . '&download=1'
            . '&title=' . urlencode($save_name);
        $download_url_js = wp_json_encode($download_url);

        $inline_js  = '<scr'.'ipt>';
        $inline_js .= 'var WPI_TITLE='.$doc_title_js.';';
        $inline_js .= 'var WPI_PRINT_URL='.$print_url_js.';';
        $inline_js .= 'var WPI_DL_URL='.$download_url_js.';';
        $inline_js .= 'var WPI_AJAX_URL='.wp_json_encode(admin_url('admin-ajax.php')).';';
        $inline_js .= 'var WPI_NONCE='.wp_json_encode(wp_create_nonce('wpi_nonce')).';';
        $inline_js .= 'var WPI_ID='.absint($_GET['id']??0).';';
        $inline_js .= 'var wpiShareTokenUrl=null;';
        $inline_js .= 'document.title=WPI_TITLE;';
        $inline_js .= $auto_print;
        // Save/Download must use the generated PDF endpoint, not browser print.
        $inline_js .= 'window.addEventListener("load",function(){var fd=new FormData();fd.append("action","wpi_create_share_token");fd.append("nonce",WPI_NONCE);fd.append("id",WPI_ID);fetch(WPI_AJAX_URL,{method:"POST",credentials:"include",body:fd}).then(function(r){return r.json();}).then(function(j){if(j.success)wpiShareTokenUrl=j.data.url;}).catch(function(){});});';
        $inline_js .= 'function wpiDownloadFromUrl(url,filename){var a=document.createElement("a");a.href=url;a.download=filename||WPI_TITLE+".pdf";a.rel="noopener";a.style.display="none";document.body.appendChild(a);a.click();setTimeout(function(){if(a.parentNode)a.parentNode.removeChild(a);},1000);}';
        $inline_js .= 'function wpiSaveReport(){document.title=WPI_TITLE;wpiDownloadFromUrl(WPI_DL_URL,WPI_TITLE+".pdf");}';

        // Keep function name for existing button handlers, but route to generated PDF download.
        $inline_js .= 'function wpiPrintPdf(){document.title=WPI_TITLE;wpiDownloadFromUrl(WPI_DL_URL,WPI_TITLE+".pdf");}';

        // wpiShareReport — on devices with native share (iOS/Android), share the print URL.
        // On desktop, open the print window directly.
        $inline_js .= 'function wpiSetShareLoading(on,msg){var o=document.getElementById("wpi-share-loading");var t=document.getElementById("wpi-share-loading-text");var b=document.getElementById("wpi-share-btn");if(t&&msg){t.textContent=msg;}if(o){o.style.display=on?"flex":"none";}if(b){b.disabled=!!on;b.style.opacity=on?"0.7":"1";b.style.pointerEvents=on?"none":"auto";}}';

        // wpiGeneratePdfBlob:
        // Temporarily hides the print-bar, captures the .page element with html2canvas,
        // then assembles into an A4 PDF via jsPDF.
        // wpiGeneratePdfBlob:
        // 1. Hides print-bar
        // 2. Nudges gallery-page-break so it lands on a fresh page boundary in the PDF
        // 3. Captures .page with html2canvas, slices into A4 pages
        // wpiWaitImages: waits for all img tags inside el to finish loading
        $inline_js .= 'function wpiWaitImages(el){'
            . 'var imgs=Array.prototype.slice.call(el.querySelectorAll("img"));'
            . 'var pending=imgs.filter(function(i){return !i.complete||i.naturalWidth===0;});'
            . 'if(pending.length===0)return Promise.resolve();'
            . 'return Promise.all(pending.map(function(img){'
            .   'return new Promise(function(res){img.onload=res;img.onerror=res;setTimeout(res,8000);});'
            . '}));'
            . '}';

        // Load PDF libs dynamically (CDN, runs on standalone endpoint — no CSP issues)
        $inline_js .= 'function wpiLoadPdfLibs(cb){'
            . 'if(typeof html2canvas!=="undefined"&&typeof jspdf!=="undefined"){cb();return;}'
            . 'function loadScript(src,next){var s=document.createElement("script");s.src=src;s.onload=next;s.onerror=function(){cb(new Error("load failed"));};document.head.appendChild(s);}'
            . 'loadScript("https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js",function(){'
            .   'loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js",function(){cb();});'
            . '});'
            . '}';

        // wpiCaptureSection: captures a DOM element to jsPDF pages
        $inline_js .= 'function wpiCaptureSection(pdf,el,addPageFirst){'
            . 'var imgs=Array.prototype.slice.call(el.querySelectorAll("img"));'
            . 'var pending=imgs.filter(function(i){return !i.complete||i.naturalWidth===0;});'
            . 'var waitP=pending.length?Promise.all(pending.map(function(img){return new Promise(function(res){img.onload=res;img.onerror=res;setTimeout(res,10000);});})):Promise.resolve();'
            . 'return waitP.then(function(){return new Promise(function(r){setTimeout(r,300);});})'
            . '.then(function(){'
            .   'var prevW=document.body.style.width;'
            .   'document.body.style.width="820px";'
            .   'void document.body.offsetHeight;'
            .   'var rect=el.getBoundingClientRect();'
            .   'var elLeft=Math.round(rect.left);'
            .   'var elTop=Math.round(rect.top+window.pageYOffset);'
            .   'var elH=Math.ceil(Math.max(el.scrollHeight,el.offsetHeight,rect.height));'
            .   'var docH=Math.ceil(document.documentElement.scrollHeight);'
            .   'return html2canvas(document.documentElement,{'
            .     'scale:2,useCORS:true,allowTaint:true,logging:false,'
            .     'windowWidth:820,windowHeight:docH,'
            .     'scrollX:0,scrollY:0,'
            .     'x:elLeft,y:elTop,width:820,height:elH'
            .   '}).then(function(c){document.body.style.width=prevW;return c;})'
            .   '.catch(function(e){document.body.style.width=prevW;throw e;});'
            . '}).then(function(canvas){'
            .   'var pdfW=pdf.internal.pageSize.getWidth();'
            .   'var pdfH=pdf.internal.pageSize.getHeight();'
            .   'var margin=20;'
            .   'var usableH=pdfH-margin*2;'
            .   'var ratio=pdfW/canvas.width;'
            .   'var totalH=canvas.height*ratio;'
            .   'var pageStarts=[0];var pos=usableH;'
            .   'while(pos<totalH){pageStarts.push(pos);pos+=usableH;}'
            .   'var jpegData=canvas.toDataURL("image/jpeg",0.88);'
            .   'for(var i=0;i<pageStarts.length;i++){'
            .     'if(i>0||addPageFirst)pdf.addPage();'
            .     'pdf.addImage(jpegData,"JPEG",0,margin-pageStarts[i],pdfW,totalH,undefined,"FAST");'
            .   '}'
            . '});'
            . '}';

        // wpiShareReport: pre-generate PDF blob in background so share fires synchronously on iOS
        // iOS Safari kills the file-share permission if navigator.share() is called after any async work.
        // Solution: generate the blob on page load, store it in wpiPdfBlob, then share instantly on tap.
        // wpiDownloadPdf: generate PDF client-side and trigger direct download (works on all platforms)
                // wpiDownloadPdf: use the same generated PDF URL as Save/Share so output stays identical.
        $inline_js .= 'function wpiDownloadPdf(){wpiSetShareLoading(true,"Preparing PDF...");setTimeout(function(){wpiSetShareLoading(false);wpiDownloadFromUrl(WPI_DL_URL,WPI_TITLE+".pdf");},50);}';

        // wpiShareReport: share the already-generated PDF file from the same download endpoint, fall back to direct download
                $inline_js .= 'function wpiShareReport(){'
            . 'if(typeof navigator.share!=="function"){wpiDownloadPdf();return;}'
            . 'wpiSetShareLoading(true,"Preparing PDF...");'
            . 'fetch(WPI_DL_URL,{credentials:"include"})'
            . '.then(function(res){if(!res.ok)throw new Error("download failed");return res.blob();})'
            . '.then(function(blob){'
            .   'var file=null;try{file=new File([blob],WPI_TITLE+".pdf",{type:"application/pdf"});}catch(e){}'
            .   'var canFiles=!!(file&&navigator.canShare&&navigator.canShare({files:[file]}));'
            .   'if(canFiles){return navigator.share({title:WPI_TITLE,files:[file]}).catch(function(err){if(err&&err.name==="AbortError")return;var url=URL.createObjectURL(blob);wpiDownloadFromUrl(url,WPI_TITLE+".pdf");setTimeout(function(){URL.revokeObjectURL(url);},60000);});}'
            .   'var url=URL.createObjectURL(blob);wpiDownloadFromUrl(url,WPI_TITLE+".pdf");setTimeout(function(){URL.revokeObjectURL(url);},60000);'
            . '})'
            . '.catch(function(){wpiDownloadPdf();})'
            . '.finally(function(){wpiSetShareLoading(false);});'
            . '}';

        $inline_js .= "window.addEventListener('beforeprint',function(){" .
            "document.title=WPI_TITLE;" .
            "try{" .
            "}catch(e){}" .
            "});";
        $inline_js .= '</'.'script>';

        $html = '<!DOCTYPE html><html lang="en"><head>
<title>'.esc_html($save_name).'</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
'.$inline_js.'
<style>
*{box-sizing:border-box;margin:0;padding:0;}
h1,h2,h3,h4,h5,h6,p{margin:0;padding:0;font-size:13px;font-weight:400;font-family:Arial,Helvetica,sans-serif;}body{font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:400;color:#1a1a1a;background:#f0f2f5;}
.page{max-width:860px;margin:0 auto;background:#fff;box-shadow:0 2px 24px rgba(0,0,0,.1);border-radius:4px;}

/* Print bar */
.print-bar{position:fixed;top:0;left:0;right:0;z-index:999;background:#fff;border-bottom:1px solid #e5e7eb;
  padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;
  box-shadow:0 2px 8px rgba(0,0,0,.08);}
.print-bar-title{font-size:14px;font-weight:700;color:#111;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;text-align:center;}
.print-btn{background:#16a34a;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;}
.back-btn{background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;}
body{padding-top:56px;}

/* Header */
.header{background:'.esc_attr($header_col).';padding:28px 28px 24px;}
.logo-img{max-height:48px;max-width:160px;object-fit:contain;display:block;margin-bottom:20px;}
.header-label{font-size:11px;font-weight:700;color:'.esc_attr($header_text).';opacity:.65;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px;}
.header-title{font-size:26px;font-weight:800;color:'.esc_attr($header_text).';line-height:1.2;margin-bottom:8px;}
.header-sub{font-size:13px;color:'.esc_attr($header_text).';opacity:.7;margin-top:6px;}
.header-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;}
.header-brand{flex:1;}
.header-status-badge{display:inline-flex;align-items:center;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);color:'.esc_attr($header_text).';border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;white-space:nowrap;margin-top:4px;}
.header-subtitle{font-size:12px;color:'.esc_attr($header_text).';opacity:.75;margin-top:6px;line-height:1.4;}

/* Summary bar (iAuditor style breadcrumb + stats) */
.summary-bar{border-bottom:1px solid #dbe2ea;padding:0 28px 14px;background:#fff;}
.summary-breadcrumb{font-size:13px;color:#374151;margin-bottom:14px;line-height:1.5;display:flex;justify-content:space-between;gap:12px;align-items:flex-start;}
.summary-status{font-weight:800;white-space:nowrap;}
.summary-stats{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid #dbe2ea;background:#fff;}
.sum-cell{display:flex;flex-direction:column;gap:2px;padding:14px 20px;border-right:1px solid #dbe2ea;}
.sum-cell:last-child{border-right:none;}
.sum-num{font-size:22px;font-weight:800;color:#111;}
.sum-label{font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}

/* Meta rows */
.meta-section{border-bottom:2px solid #e5e7eb;}
.meta-row{display:grid;grid-template-columns:180px 1fr;padding:11px 20px;border-bottom:1px solid #f3f4f6;align-items:baseline;}
.meta-row:last-child{border-bottom:none;}
.meta-label{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;}
.meta-value{font-size:13px;color:#111;font-weight:500;text-align:right;}

/* Sections */
.section{margin-bottom:12px;}
.section-header{display:flex;align-items:center;justify-content:space-between;background:#E9ECF7;padding:10px 16px;border-top:1px solid #c5cae9;border-bottom:1px solid #c5cae9;}
.section-name{font-size:13px;font-weight:800;color:#1a1a2e;}
.section-score{font-size:11px;font-weight:700;color:#4b5563;opacity:1;}

/* Rows */
.row{display:grid;grid-template-columns:180px 1fr;column-gap:16px;row-gap:0;padding:11px 20px;border-bottom:1px solid #f1f5f9;align-items:center;orphans:2;widows:2;}
.row:last-child{border-bottom:none;}
.row-flagged{background:#fffbeb;}
.row-label{font-size:13px;color:#1a1a2e;font-weight:700;line-height:1.4;grid-column:1;grid-row:1;font-family:Arial,Helvetica,sans-serif;}
.row-value{grid-column:2;grid-row:1;text-align:right;font-size:13px;font-weight:400;color:#374151;line-height:1.5;word-break:break-word;vertical-align:middle;}.chip-row{border-bottom:1px solid #f1f5f9;border-collapse:collapse;break-inside:avoid;page-break-inside:avoid;}.chip-row-label{font-size:13px;color:#1a1a2e;font-weight:700;line-height:1.4;font-family:Arial,Helvetica,sans-serif;padding:11px 8px 11px 20px;width:55%;vertical-align:middle;}.chip-row-value{padding:8px 20px 8px 8px;text-align:right;vertical-align:middle;}.answer-chip{display:inline-block;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.row-value-block{grid-column:1/-1;margin-top:4px;font-size:13px;font-weight:400;color:#374151;line-height:1.6;white-space:pre-wrap;word-break:break-word;font-family:Arial,Helvetica,sans-serif;}.row-value-block .val-text{font-size:13px!important;font-weight:400!important;line-height:1.6;color:#111;text-align:left!important;max-width:none!important;display:block;}
.row-note{grid-column:1/-1;margin-top:6px;font-size:11px;color:#6b7280;background:#f9fafb;padding:6px 10px;border-radius:4px;border-left:2px solid #e5e7eb;}.row-note-required{color:#5b21b6;background:#f5f3ff;border-left:2px solid #8b5cf6;}
.photo-strip{grid-column:1/-1;display:grid;grid-template-columns:repeat(10,1fr);gap:3px;margin-top:6px;}
.photo-thumb{display:flex;flex-direction:column;align-items:center;gap:3px;}
.photo-thumb img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:3px;border:1px solid #e5e7eb;display:block;}
.photo-num{font-size:8px;color:#9ca3af;font-weight:600;}
.row-instruction{display:block;width:100%;box-sizing:border-box;font-size:12px;color:#1e3a8a;background:#eff6ff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;border-left:4px solid #3b82f6;border-top:1px solid #bfdbfe;border-bottom:1px solid #bfdbfe;padding:10px 16px;white-space:pre-wrap;word-wrap:break-word;line-height:1.7;margin:2px 0;}.row-instruction .info-label,.row-instruction strong.info-label{font-size:10px;font-weight:700;color:#1d4ed8!important;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px;}@media print{.row-instruction{background:#eff6ff!important;color:#1e3a8a!important;border-left:4px solid #3b82f6!important;}}

/* Child rows */
.child-row{grid-column:1/-1;display:block;padding:8px 20px 8px 36px;border-bottom:1px solid #f8fafc;background:#fafafa;break-inside:avoid;page-break-inside:avoid;border-left:3px solid '.esc_attr($accent_col).';margin:0 -20px;}
.child-label{font-size:11px;color:#6b7280;line-height:1.4;margin-bottom:3px;font-style:italic;}
.child-value{font-size:13px;color:#111;line-height:1.5;word-break:break-word;overflow-wrap:break-word;font-weight:500;}

/* Response values */
.chip-yes{display:inline-flex;align-items:center;gap:4px;background:#15803d;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-no{display:inline-flex;align-items:center;gap:4px;background:#dc2626;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-na{display:inline-flex;align-items:center;gap:4px;background:#6b7280;color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-signed{display:inline-flex;align-items:center;gap:4px;background:#15803d;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-mc{display:inline-block;font-size:12px;font-weight:700;padding:4px 12px;border-radius:5px;white-space:nowrap;line-height:1.4;vertical-align:middle;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.val-text{font-size:13px;font-weight:400;color:#111;text-align:right;line-height:1.4;word-break:break-word;white-space:pre-wrap;font-family:Arial,Helvetica,sans-serif;overflow-wrap:break-word;display:block;}
.val-photo{font-size:12px;color:#374151;font-weight:600;}
.val-empty{font-size:13px;color:#d1d5db;}
.val-rating{font-size:15px;color:#f59e0b;letter-spacing:2px;}
.flag-pill{background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;white-space:nowrap;}

/* Signature */
.sig-row-inner{grid-column:1/-1;break-inside:avoid;page-break-inside:avoid;}.sig-block{padding:16px 20px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;margin-top:8px;display:inline-block;max-width:340px;break-inside:avoid;page-break-inside:avoid;}
.sig-img{display:block;width:300px;height:130px;border:1.5px solid #d1d5db;border-radius:6px;object-fit:contain;background:#fff;margin-bottom:8px;}
.sig-meta{display:flex;flex-direction:column;gap:2px;border-top:1px solid #e5e7eb;padding-top:8px;}
.sig-name{font-size:13px;font-weight:700;color:#111;}
.sig-date{font-size:11px;color:#6b7280;}

/* Flagged summary */
.flagged-summary{margin:16px 0;border:1.5px solid #f59e0b;border-radius:8px;overflow:hidden;}
.flagged-summary-header{background:#fef3c7;padding:10px 20px;font-size:13px;font-weight:800;color:#92400e;border-bottom:1px solid #fde68a;}
.flagged-row{display:grid;grid-template-columns:140px 1fr auto;column-gap:12px;padding:9px 20px;border-bottom:1px solid #fef9c3;align-items:start;background:#fffbeb;}
.flagged-row:last-child{border-bottom:none;}
.flagged-section{font-size:10px;font-weight:700;color:#d97706;text-transform:uppercase;letter-spacing:.4px;padding-top:2px;}
.flagged-label{font-size:13px;font-weight:600;color:#374151;}
.flagged-value{font-size:12px;color:#111;text-align:right;}
.flagged-note{grid-column:2/-1;font-size:11px;color:#92400e;background:#fef3c7;padding:4px 8px;border-radius:4px;margin-top:4px;}

/* Auditor notes */
.auditor-notes{padding:16px 20px;background:#fffbeb;border-top:2px solid #fde68a;}
.notes-header{font-size:11px;font-weight:700;color:#92400e;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
.notes-text{font-size:12px;color:#555;line-height:1.6;}

/* Footer */
.content-flow{padding-bottom:'.esc_attr($content_gap_css).';}
.footer{padding:8px 20px;font-size:10px;color:#9ca3af;border-top:1px solid #e5e7eb;background:#fff;}
.footer-inner{display:flex;justify-content:space-between;align-items:center;gap:8px;}
.footer-left{text-align:left;flex:1;}
.footer-center{text-align:center;flex:1;}
.footer-right{text-align:right;flex:1;}
.footer-page{float:right;}

/* Gallery */
.page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;border:none;margin:0;padding:0;}.template-page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;line-height:1px;}
.page-break-odd{break-before:right;page-break-before:right;}
.page-break-odd{break-before:right;page-break-before:right;}
.gallery-page-break{display:block;page-break-before:always;}
.gallery-page{max-width:820px;margin:0 auto;padding:34px 24px 20px;background:#fff;}
.gallery-header{display:flex;align-items:center;justify-content:space-between;padding-top:10px;padding-bottom:8px;border-bottom:2px solid #e5e7eb;margin-bottom:10px;min-height:56px;}
.gallery-title{font-size:15px;font-weight:800;color:#111;}
.gallery-chunk{margin-bottom:0;}
.gallery-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:stretch;margin-bottom:0;break-inside:avoid;page-break-inside:avoid;}
.gallery-cell{display:flex;flex-direction:column;gap:8px;min-width:0;}
.gallery-img-wrap{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;aspect-ratio:4/3;padding:10px;}
.gallery-img-wrap img{width:100%;height:100%;object-fit:contain;display:block;}
.gallery-page-divider{display:flex;align-items:center;justify-content:space-between;padding:16px 0 8px;border-top:2px solid #e5e7eb;margin-top:4px;}
.gallery-cap{font-size:10px;color:#6b7280;padding:0 2px;min-height:24px;text-align:center;}
.gallery-cap strong{color:#374151;display:block;font-size:11px;} .gallery-cell-empty{visibility:hidden;} .gallery-img-wrap-empty{background:transparent;border:1px dashed transparent;} .gallery-cap-empty{visibility:hidden;} .gallery-err{display:flex;align-items:center;justify-content:center;height:100%;width:100%;font-size:11px;color:#6b7280;text-align:center;padding:12px;}
.gallery-err{width:100%;height:220px;display:flex;align-items:center;justify-content:center;font-size:12px;color:#9ca3af;background:#f9fafb;}

@page{margin:'.esc_attr($page_margin_css).';}
@media print{
  body{background:#fff;padding-top:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;counter-reset:wpi-page;}
  .page{box-shadow:none;max-width:100%;margin:0;border-radius:0;}
  .content-flow{padding-bottom:0;}
  .footer{position:fixed;left:0;right:0;bottom:0;padding:'.esc_attr($footer_pad_css).';min-height:12mm;background:#fff;z-index:20;}
  .print-bar{display:none!important;}
  .page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;}
  .gallery-page-break{page-break-before:always;}
  .gallery-page{break-inside:avoid;page-break-inside:avoid;padding-top:34px;}
  /* Keep section blocks together where possible so headers do not split awkwardly */
  .section{display:block;break-inside:avoid;page-break-inside:avoid;}
  .section-header{break-after:avoid;page-break-after:avoid;break-inside:avoid;page-break-inside:avoid;margin-top:8px;}
  .row{break-inside:avoid;page-break-inside:avoid;}
  .row-sig{break-inside:auto;page-break-inside:auto;}
  .row-sig .sig-row-inner{break-before:avoid;page-break-before:avoid;}
  .sig-row-inner{break-inside:avoid;page-break-inside:avoid;}
  .sig-block{break-inside:avoid;page-break-inside:avoid;}
  .sig-meta{break-inside:avoid;page-break-inside:avoid;}
  .header{break-inside:avoid;page-break-inside:avoid;}
}
.wpi-standalone{padding-top:0!important;}

.wpi-share-loading{position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,.92);display:none;align-items:center;justify-content:center;padding:24px;}
.wpi-share-loading-box{min-width:220px;max-width:320px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.12);padding:22px 18px;text-align:center;}
.wpi-share-spinner{width:42px;height:42px;border:4px solid #e5e7eb;border-top-color:#1a5276;border-radius:50%;margin:0 auto 14px;animation:wpi-spin 1s linear infinite;}
.wpi-share-loading-text{font-size:14px;font-weight:600;color:#374151;}
@keyframes wpi-spin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
</style>
<!-- PDF libs loaded on-demand by wpiShareReport -->
</head><body>

<div class="print-bar">
  <button class="back-btn" onclick="window.parent.postMessage({wpiAction:&apos;closeReport&apos;},&apos;*&apos;)" title="Close">&#10005;</button>
  <span class="print-bar-title">'.esc_html($report_title).'</span>
  <div style="display:flex;gap:8px;flex-shrink:0;">
    <button class="print-btn" onclick="wpiPrintPdf()" title="Download PDF" style="background:#1a5276;">&#128438; Save PDF</button>
    <button id="wpi-share-btn" class="print-btn" onclick="wpiShareReport()" title="Share PDF">&#11014; Share PDF</button>
  </div>
</div>

<div id="wpi-share-loading" class="wpi-share-loading" aria-hidden="true">
  <div class="wpi-share-loading-box">
    <div class="wpi-share-spinner"></div>
    <div id="wpi-share-loading-text" class="wpi-share-loading-text">Preparing PDF...</div>
  </div>
</div>

<div class="page">
  <div class="content-flow">
  '.( (!empty($cfg['logo_url']) || !empty($cfg['report_title']) || $show_summary_bar || $meta_html)
    ? '<div class="header">
    <div class="header-top">
      <div class="header-brand">
        '.$logo_html.'
        '.($show_audit_title ? '<div class="header-title">'.esc_html($report_title).'</div>' : '').'
        '.( $show_site_hdr || $show_date_hdr
          ? '<div class="header-subtitle">'.esc_html(trim($site.($site && $inspection->template_title ? ' / ' : '').($inspection->template_title ?: '').' / '.$date_str)).'</div>'
          : '' ).'
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div class="header-status-badge" style="background:'.esc_attr($status_color).'">'.esc_html($status).'</div>
        '.($show_score_hdr && $score!==null ? '<div style="margin-top:8px;font-size:28px;font-weight:900;color:'.esc_attr($header_text).';">'.esc_html($score_str).'</div><div style="font-size:10px;color:'.esc_attr($header_text).';opacity:.65;text-transform:uppercase;letter-spacing:.5px;">Score</div>' : '').'
      </div>
    </div>
  </div>'
    : '' ).'

  '.$summary_html.'

  '.($meta_html ? '<div class="meta-section">'.$meta_html.'</div>' : '').'

  '.$body_html.'

  '.$flagged_summary_html.'

  '.$notes_html.'
  </div>

  <div class="footer">
    <div class="footer-inner">
      <span class="footer-left">'.self::resolve_footer_tokens($footer_left_raw ?: $footer_text_legacy, $inspection, $site, $date_str).'</span>
      <span class="footer-center">'.self::resolve_footer_tokens($footer_center_raw, $inspection, $site, $date_str).'</span>
      <span class="footer-right footer-page-token">'.self::resolve_footer_tokens($footer_right_raw, $inspection, $site, $date_str).'</span>
    </div>
  </div>
</div>

'.($show_gallery_pg ? $gallery_html : '').'

</body></html>';

        while (ob_get_level()) ob_end_clean();
        error_reporting( isset($prev_error_reporting) ? $prev_error_reporting : E_ALL );
        // for use by the client-side html2canvas PDF generator (iframe capture).
        if ( ! empty( $_GET['pdf_render'] ) ) {
            $render_html = self::build_rich_html( $inspection_id );
            if ( $render_html ) {
                header('Content-Type: text/html; charset=UTF-8');
                header('Cache-Control: no-cache, no-store');
                header('X-Frame-Options: SAMEORIGIN');
                echo $render_html;
                exit;
            }
        }

        $is_download  = ! empty( $_GET['download'] );
        $ext        = $is_download ? '.pdf' : '.html';
        $filename   = $save_name . $ext;
        $ascii_name = preg_replace('/[^\x20-\x7E]/', '_', $filename);

        if ( $is_download ) {
            // 1. Try wkhtmltopdf (server-side, pixel-perfect)
            $pdf_file = self::html_to_pdf( $html, $inspection_id, $save_name, $cfg, $inspection, $site, $date_str );

            // 2. Try headless Chrome/Chromium
            if ( ! $pdf_file || ! file_exists( $pdf_file ) ) {
                $pdf_file = self::html_to_pdf_chrome( $html, $inspection_id, $save_name );
            }

            // 3. Binary PHP PDF engine fallback (works for normal inspections)
            if ( ! $pdf_file || ! file_exists( $pdf_file ) ) {
                if ( ! class_exists( 'WPI_PDF_Email' ) ) {
                    require_once WPI_PLUGIN_DIR . 'includes/class-pdf-email.php';
                }
                try {
                    $pdf_file = WPI_PDF_Email::get_pdf_file( $inspection_id );
                } catch ( Throwable $e ) {
                    $pdf_file = null;
                }
            }

            // 4. HTML auto-print fallback (last resort — opens in browser for print-to-PDF)
            if ( ! $pdf_file || ! file_exists( $pdf_file ) ) {
                $auto_print_snippet = 'window.addEventListener("load",function(){'
                    . 'document.title=WPI_TITLE;'
                    . 'setTimeout(function(){window.print();},900);'
                    . '});';
                $html_out = str_replace(
                    'window.addEventListener("beforeprint"',
                    $auto_print_snippet . 'window.addEventListener("beforeprint"',
                    $html
                );
                header('Content-Type: text/html; charset=UTF-8');
                header('Cache-Control: no-cache, no-store');
                header('X-WPI-Title: ' . $ascii_name);
                while ( ob_get_level() ) ob_end_clean();
                echo $html_out;
                exit;
            }

            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"{$ascii_name}\"; filename*=UTF-8''" . rawurlencode($filename));
            header('Content-Length: ' . filesize( $pdf_file ));
            header('Cache-Control: no-cache, no-store');
            header('X-WPI-Title: ' . $ascii_name);
            readfile( $pdf_file );
            @unlink( $pdf_file );
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        header("Content-Disposition: inline; filename=\"{$ascii_name}\"; filename*=UTF-8''" . rawurlencode($filename));
        header('Cache-Control: no-cache, no-store');
        header('X-WPI-Title: '.$ascii_name);
        // Flush any open output buffers so the HTML is sent directly, not swallowed
        while ( ob_get_level() ) ob_end_clean();
        echo $html;
        exit;
    }

    /**
     * Build the HTML report and save to a temp file.
     * Returns the temp file path, or null on failure.
     * Caller must unlink() the file after use.
     */
    public static function get_html_file( $inspection_id ) {
        global $wpdb;

        // Re-run the same data queries as generate()
        $inspection = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                    t.title as template_title, t.settings as t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.id=%d", $inspection_id
        ) );
        if ( ! $inspection ) return null;

        // Capture the HTML by using output buffering around generate()
        // generate() calls exit() — so we use a shutdown trick instead: write
        // to a temp path by invoking generate() inside a forked ob context.
        // Simplest safe approach: instantiate and call generate() with ob + register_shutdown_function
        // to intercept exit.
        //
        // Cleanest real solution: use ob_start with a callback to capture output.
        ob_start();
        // Suppress headers (they no-op when output already buffered in most PHP configs)
        $instance = new self();
        // We need to capture $html without exit(). Since generate() exits,
        // we rebuild $html using the same logic directly here.
        ob_end_clean();

        // Build HTML directly (mirrors generate() logic exactly)
        $cfg = array(
            'show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,
            'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,'show_flagged_summary'=>false,
            'show_date'=>false,'show_site'=>false,'show_gallery'=>true,'show_section_scores'=>false,
            'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c',
            'logo_url'=>'','logo_position'=>'left','report_title'=>'',
            'footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}',
            'footer_text'=>'','page_margin'=>'normal','pdf_filename'=>'{template}/{site}/{date}',
        );
        $t_cfg = $inspection->t_settings ? json_decode( $inspection->t_settings, true ) : array();
        if ( is_array( $t_cfg ) ) $cfg = array_merge( $cfg, $t_cfg );

        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d ORDER BY sort_order", $inspection->template_id
        ) );
        $responses = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d", $inspection_id
        ) );

        $rmap = array(); $child_rmap = array();
        foreach ( $responses as $r ) {
            $raw = $r->question_id;
            if ( is_numeric( $raw ) ) { $rmap[(int)$raw] = $r; }
            else {
                $ph = $r->photos ? json_decode( $r->photos, true ) : array();
                $r->photos = is_array( $ph ) ? array_values( array_filter( array_map( function($x){
                    return is_array($x) && !empty($x['url']) ? $x : null;
                }, $ph ) ) ) : array();
                $child_rmap[$raw] = $r;
            }
        }

        // Build title
        $raw_title_tpl = trim( $cfg['report_title'] ?? '' );
        if ( $raw_title_tpl ) {
            $q_map_t = array();
            foreach ( $questions as $item ) {
                if ( in_array( $item->type, array('instruction','page') ) ) continue;
                $slug = strtolower( preg_replace( '/[^a-z0-9]+/i', '_', $item->label ?? '' ) );
                $slug = trim( $slug, '_' );
                $slug = substr( $slug, 0, 40 );
                if ( $slug && isset( $rmap[$item->id] ) ) $q_map_t[$slug] = self::format_token_value_by_type( $rmap[$item->id]->value, $item->type ?? '' );
            }
            if ( empty($q_map_t['site']) && !empty($inspection->site_name) ) {
                $q_map_t['site'] = $inspection->site_name;
            }
            $dt_t = !empty( $inspection->conducted_at ) ? new DateTime( $inspection->conducted_at ) : new DateTime();
            $res  = $raw_title_tpl;
            $res  = str_replace( '{date}',      $dt_t->format('d M Y'),                              $res );
            $res  = str_replace( '{time}',      $dt_t->format('g:i A'),                              $res );
            $res  = str_replace( '{template}',  $inspection->template_title ?? $inspection->title,   $res );
            $res  = str_replace( '{site}',      $inspection->site_name ?: '',                        $res );
            $res  = str_replace( '{inspector}', $inspection->inspector_name ?? '',                   $res );
            $res  = str_replace( '{score}',     ( $inspection->score !== null ? round( $inspection->score ).'%' : '' ), $res );
            $res  = preg_replace_callback( '/\{field:([^}]+)\}/', function($m) use ($q_map_t) {
                $t = trim( $m[1] );
                if ( isset( $q_map_t[$t] ) ) return $q_map_t[$t];
                foreach ( $q_map_t as $k => $v ) { if ( strpos( $k, $t ) === 0 ) return $v; }
                return '';
            }, $res );
            $doc_title = trim( preg_replace( '/\s+/', ' ', $res ) ) ?: $inspection->title;
        } else {
            $doc_title = $inspection->title;
        }

        // Use generate() via output buffer to get the HTML — but since it calls exit,
        // we instead just produce a minimal HTML wrapper pointing to the real report.
        // For the email attachment we produce a self-contained summary HTML.
        $score_str = $inspection->score !== null ? round( $inspection->score ) . '%' : '—';
        $dt        = !empty( $inspection->completed_at ) ? new DateTime( $inspection->completed_at ) : ( !empty( $inspection->conducted_at ) ? new DateTime( $inspection->conducted_at ) : new DateTime() );
        $header_col = '#ffffff';

        // Build a simple but complete summary HTML for the attachment
        $rows_html = '';
        foreach ( $questions as $q ) {
            if ( in_array( $q->type, array('instruction','page') ) ) continue;
            $resp  = isset( $rmap[$q->id] ) ? $rmap[$q->id] : null;
            $val   = $resp ? htmlspecialchars( (string)$resp->value ) : '<em style="color:#999">—</em>';
            $flag  = $resp && $resp->flagged ? ' <span style="color:#dc2626;font-size:11px">⚑ Flagged</span>' : '';
            $notes = $resp && $resp->notes ? '<div style="font-size:11px;color:#666;margin-top:3px">Note: '.htmlspecialchars($resp->notes).'</div>' : '';
            $rows_html .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#374151;width:55%">'.htmlspecialchars($q->label ?? '').'</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#111;font-weight:500">'.$val.$flag.$notes.'</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
              . '<title>'.htmlspecialchars($doc_title).'</title>'
              . '<style>body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f9fafb;}'
              . '.header{background:'.htmlspecialchars($header_col).';padding:24px 28px;}'
              . '.header h1{color:#fff;margin:0;font-size:20px;} .header p{color:rgba(255,255,255,.8);margin:4px 0 0;font-size:13px;}'
              . '.meta{display:flex;gap:24px;padding:16px 28px;background:#fff;border-bottom:1px solid #e5e7eb;flex-wrap:wrap;}'
              . '.meta-item{font-size:12px;color:#6b7280;} .meta-item strong{display:block;color:#111;font-size:14px;}'
              . '.score-badge{background:'.htmlspecialchars($header_col).';color:#fff;padding:4px 14px;border-radius:20px;font-size:15px;font-weight:700;}'
              . 'table{width:100%;border-collapse:collapse;background:#fff;margin:16px 0;}'
              . 'th{text-align:left;padding:10px 12px;background:#f8fafc;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;border-bottom:2px solid #e5e7eb;}'
              . '</style></head><body>'
              . '<div class="header"><h1>'.htmlspecialchars($doc_title).'</h1>'
              . '<p>'.htmlspecialchars($inspection->template_title ?? '').'</p></div>'
              . '<div class="meta">'
              . ( self::cfg_on($cfg,'show_inspector',false) ? '<div class="meta-item"><strong>'.htmlspecialchars($inspection->inspector_name ?? '—').'</strong>Inspector</div>' : '' )
              . ( self::cfg_on($cfg,'show_date',false)      ? '<div class="meta-item"><strong>'.htmlspecialchars($dt->format('d M Y, H:i')).'</strong>Date</div>' : '' )
              . ( self::cfg_on($cfg,'show_site',false) && $inspection->site_name ? '<div class="meta-item"><strong>'.htmlspecialchars($inspection->site_name).'</strong>Site</div>' : '' )
              . ( self::cfg_on($cfg,'show_score',false)     ? '<div class="meta-item"><strong><span class="score-badge">'.$score_str.'</span></strong>Score</div>' : '' )
              . '</div>'
              . '<div style="padding:0 16px 24px">'
              . '<table><thead><tr><th>Question</th><th>Answer</th></tr></thead><tbody>'
              . $rows_html
              . '</tbody></table></div>'
              . '</body></html>';

        $safe  = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $doc_title ?: 'report' );
        $path  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpi_report_' . $inspection_id . '_' . $safe . '.html';
        file_put_contents( $path, $html );
        return $path;
    }

    /**
     * For chip-type answers, returns array('label'=>..., 'bg'=>..., 'fg'=>...)
     * Used by chip-row builder to put bgcolor directly on the TD.
     * Returns null if not a chip type or no value.
     */
    private static function chip_data( $item, $vl ) {
        if ($item->type === 'yes_no') {
            $ync = isset($item->yes_no_colors) && is_array($item->yes_no_colors) ? $item->yes_no_colors : array();
            $yc  = isset($ync['yes']) && $ync['yes'] ? $ync['yes'] : '#15803d';
            $nc  = isset($ync['no'])  && $ync['no']  ? $ync['no']  : '#dc2626';
            $ac  = isset($ync['na'])  && $ync['na']  ? $ync['na']  : '#6b7280';
            $ytc = isset($ync['yes_text']) && $ync['yes_text'] ? $ync['yes_text'] : '#ffffff';
            $ntc = isset($ync['no_text'])  && $ync['no_text']  ? $ync['no_text']  : '#ffffff';
            $atc = isset($ync['na_text'])  && $ync['na_text']  ? $ync['na_text']  : '#ffffff';
            if ($vl==='yes') return array('label'=>'&#10003; Yes','bg'=>$yc,'fg'=>$ytc);
            if ($vl==='no')  return array('label'=>'&#10007; No', 'bg'=>$nc,'fg'=>$ntc);
            if ($vl==='n/a'||$vl==='na') return array('label'=>'N/A','bg'=>$ac,'fg'=>$atc);
            return null;
        }
        if (in_array($item->type, array('multiple_choice','select','dropdown','checkbox','radio','multi_select'))) {
            if ($item->value === '') return null;
            $opts = array();
            if (isset($item->options) && is_array($item->options)) {
                $opts = $item->options;
            } elseif (isset($item->options) && is_string($item->options) && $item->options) {
                $decoded = json_decode($item->options, true);
                if (is_array($decoded)) $opts = $decoded;
            }
            foreach ($opts as $opt) {
                if (is_array($opt) && !empty($opt['color'])) {
                    $ol = $opt['label'] ?? $opt['value'] ?? '';
                    if (strtolower($ol) === strtolower($item->value)) {
                        return array('label'=>esc_html($item->value),'bg'=>$opt['color'],'fg'=>'#000000');
                    }
                }
            }
            // No colour — neutral grey
            return array('label'=>esc_html($item->value),'bg'=>'#e5e7eb','fg'=>'#374151');
        }
        return null;
    }

    private static function response_value( $item, $vl, $embedded ) {
        if ($item->type === 'yes_no') {
            $ync = isset($item->yes_no_colors) && is_array($item->yes_no_colors) ? $item->yes_no_colors : array();
            $yc = isset($ync['yes']) && $ync['yes'] ? esc_attr($ync['yes']) : '#15803d';
            $nc = isset($ync['no'])  && $ync['no']  ? esc_attr($ync['no'])  : '#dc2626';
            $ac = isset($ync['na'])  && $ync['na']  ? esc_attr($ync['na'])  : '#6b7280';
            $ytc = isset($ync['yes_text']) && $ync['yes_text'] ? esc_attr($ync['yes_text']) : '#ffffff';
            $ntc = isset($ync['no_text'])  && $ync['no_text']  ? esc_attr($ync['no_text'])  : '#ffffff';
            $atc = isset($ync['na_text'])  && $ync['na_text']  ? esc_attr($ync['na_text'])  : '#ffffff';
            if ($vl==='yes') return '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-left:auto;">'.'<tr><td bgcolor="'.$yc.'" style="background:'.$yc.';color:'.$ytc.';font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">&#10003; Yes</td></tr></table>';
            if ($vl==='no')  return '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-left:auto;">'.'<tr><td bgcolor="'.$nc.'" style="background:'.$nc.';color:'.$ntc.';font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">&#10007; No</td></tr></table>';
            if ($vl==='n/a'||$vl==='na') return '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-left:auto;">'.'<tr><td bgcolor="'.$ac.'" style="background:'.$ac.';color:'.$atc.';font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">N/A</td></tr></table>';
            if ($vl==='n/a') return '<span class="chip-na">N/A</span>';
            return '<span class="val-empty">—</span>';
        }
        if ($item->type === 'signature') {
            $has = !empty($item->value);
            return $has ? '<span class="chip-signed">&#10003; Signed</span>' : '<span class="val-empty">—</span>';
        }
        if ($item->type === 'photo') {
            $cnt = count($item->photos??array());
            return $cnt>0 ? '<span class="val-photo">&#128247; '.$cnt.' photo'.($cnt!==1?'s':'').'</span>' : '<span class="val-empty">No photo</span>';
        }
        if ($item->type === 'rating') {
            $rv = intval($item->value);
            if ($rv>0) return '<span class="val-rating">'.str_repeat('&#9733;',$rv).str_repeat('&#9734;',5-$rv).'</span>';
            return '<span class="val-empty">—</span>';
        }
        if (in_array($item->type, array('gps','location')) && $item->value) {
            $gps  = json_decode($item->value, true);
            if (is_array($gps) && !empty($gps['address'])) {
                $addr = $gps['address'];
                // Strip any trailing coordinate text e.g. "(-37.95, 145.05)"
                $addr = preg_replace('/\s*\([-\d.]+,\s*[-\d.]+\)\s*$/', '', $addr);
                $addr = trim($addr);
            } elseif (is_array($gps) && isset($gps['lat']) && isset($gps['lng'])) {
                $addr = $gps['lat'].', '.$gps['lng'];
            } else {
                // Raw string — strip JSON/coordinates if present
                $addr = is_string($item->value) ? $item->value : '';
                $addr = preg_replace('/\([-\d.]+,\s*[-\d.]+\)/', '', $addr);
                $addr = trim($addr, " \t\n\r\0\x0B,");
            }
            $out = '<span class="val-text">&#128205; '.esc_html($addr).'</span>';
            // Add Geoapify static map image if lat/lng available
            if (is_array($gps) && !empty($gps['lat']) && !empty($gps['lng'])) {
                $lat = $gps['lat']; $lng = $gps['lng'];
                $map_url = "https://maps.geoapify.com/v1/staticmap?style=osm-bright&width=600&height=250&center=lonlat:{$lng},{$lat}&zoom=16&marker=lonlat:{$lng},{$lat};type:material;color:%23ff0000;size:large&apiKey=33bad1af2e854e8087f63ea08b2622a9";
                $out .= '<br><img src="'.esc_url($map_url).'" style="max-width:100%;margin-top:6px;border-radius:6px;border:1px solid #e5e7eb;" />';
            }
            return $out;
        }
        if ($item->type === 'instruction' || $item->type === 'page') return '';
        if ($item->value !== '') {
            $display_value = $item->value;
            // Detect location JSON stored in any field type
            if (is_string($item->value) && substr(trim($item->value),0,1) === '{') {
                $maybe_loc = json_decode($item->value, true);
                if (is_array($maybe_loc) && (isset($maybe_loc['lat']) || isset($maybe_loc['address']))) {
                    $addr = '';
                    if (!empty($maybe_loc['address'])) {
                        $addr = preg_replace('/\s*\([-\d.]+,\s*[-\d.]+\)\s*$/', '', $maybe_loc['address']);
                        $addr = trim($addr);
                    } elseif (isset($maybe_loc['lat']) && isset($maybe_loc['lng'])) {
                        $addr = $maybe_loc['lat'].', '.$maybe_loc['lng'];
                    }
                    $out = '<span class="val-text">&#128205; '.esc_html($addr).'</span>';
                    if (!empty($maybe_loc['lat']) && !empty($maybe_loc['lng'])) {
                        $lat = $maybe_loc['lat']; $lng = $maybe_loc['lng'];
                        $map_url = "https://maps.geoapify.com/v1/staticmap?style=osm-bright&width=600&height=250&center=lonlat:{$lng},{$lat}&zoom=16&marker=lonlat:{$lng},{$lat};type:material;color:%23ff0000;size:large&apiKey=33bad1af2e854e8087f63ea08b2622a9";
                        $out .= '<br><img src="'.esc_url($map_url).'" style="max-width:100%;margin-top:6px;border-radius:6px;border:1px solid #e5e7eb;" />';
                    }
                    return $out;
                }
            }
            if ( in_array($item->type, array('datetime','date_time'), true) ) {
                $display_value = self::format_display_datetime( $item->value );
            }
            // Multiple choice: check if selected option has a custom colour
            if ( in_array($item->type, array('multiple_choice','select','dropdown','checkbox','radio','multi_select')) ) {
                $opts = array();
                if ( isset($item->options) && is_array($item->options) ) {
                    $opts = $item->options;
                } elseif ( isset($item->options) && is_string($item->options) && $item->options ) {
                    $decoded = json_decode($item->options, true);
                    if (is_array($decoded)) $opts = $decoded;
                }
                foreach ($opts as $opt) {
                    if (is_array($opt) && !empty($opt['color'])) {
                        $ol = $opt['label'] ?? $opt['value'] ?? '';
                        if (strtolower($ol) === strtolower($display_value)) {
                            $bg = esc_attr($opt['color']);
                            $fg = '#000000'; // Black text always readable on the 10 preset light colours
                            return '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-left:auto;">'. '<tr><td bgcolor="'.$bg.'" style="background:'.$bg.';color:#000000;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">'. esc_html($display_value) .'</td></tr></table>';
                        }
                    }
                }
                // No custom colour found — render as neutral chip for multiple choice
                return '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-left:auto;">'. '<tr><td bgcolor="#e5e7eb" style="background:#e5e7eb;color:#374151;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;">'. esc_html($display_value) .'</td></tr></table>';
            }
            return '<span class="val-text">'.esc_html($display_value).'</span>';
        }
        return '<span class="val-empty">—</span>';
    }

    /**
     * Public entry point for email/external callers.
     * Builds the full rich HTML (same as the view) and converts to PDF.
     * Returns temp file path or null. Caller must unlink().
     */
    public static function get_rich_pdf_file( $inspection_id ) {
        global $wpdb;

        $inspection = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                    t.title as template_title, t.settings as t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.id=%d", $inspection_id
        ) );
        if ( ! $inspection ) return null;

        // Build the rich HTML by spoofing the GET params generate() reads
        $orig_get = $_GET;
        $_GET['id']    = $inspection_id;
        $_GET['nonce'] = '';   // not needed — we're generating internally

        // Capture output from generate() — it calls exit(), so use a shutdown trick:
        // Instead of that, we rebuild $html using the same self-contained helper.
        // Restore GET after.
        $html = self::build_rich_html( $inspection_id );
        $_GET = $orig_get;

        if ( ! $html ) return null;

        $cfg       = array( 'report_title' => '' );
        $t_cfg     = $inspection->t_settings ? json_decode( $inspection->t_settings, true ) : array();
        if ( is_array( $t_cfg ) ) $cfg = array_merge( $cfg, $t_cfg );
        $save_name = trim( $cfg['report_title'] ?? '' ) ?: ( $inspection->title ?: 'Inspection Report' );
        $save_name = preg_replace( '/[\x00-\x1f\x7f\/\\\\:*?"<>|]/u', '-', $save_name );
        $save_name = trim( preg_replace( '/\s+/', ' ', $save_name ) ) ?: 'Inspection Report';

        $site = $inspection->site_name ?: '';
        $date_str = !empty($inspection->conducted_at) ? self::format_display_datetime( $inspection->conducted_at ) : '';

        $pdf_file = self::html_to_pdf( $html, $inspection_id, $save_name, $cfg, $inspection, $site, $date_str );
        if ( ! $pdf_file || ! file_exists( $pdf_file ) ) {
            $pdf_file = self::html_to_pdf_chrome( $html, $inspection_id, $save_name );
        }
        return ( $pdf_file && file_exists( $pdf_file ) ) ? $pdf_file : null;
    }

    /**
     * Build the full view HTML for an inspection without sending headers or exit()ing.
     * Mirrors generate() but returns the HTML string.
     */
    public static function build_rich_html( $inspection_id ) {
        global $wpdb;

        $inspection = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                    t.title as template_title, t.settings as t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.id=%d", $inspection_id
        ) );
        if ( ! $inspection ) return null;

        // Temporarily set GET vars so generate() internals work, then capture via ob
        $orig_get    = $_GET;
        $orig_server = $_SERVER;
        $_GET = array( 'id' => $inspection_id, 'nonce' => '', 'wpi_pdf' => '1' );

        ob_start();
        // generate() calls exit() — we need to intercept before that.
        // Use register_shutdown_function to grab the buffer on exit.
        $captured = null;
        register_shutdown_function( function() use ( &$captured ) {
            $captured = ob_get_clean();
        } );

        // We can't safely call generate() due to exit(). Instead re-run the
        // HTML-building pipeline here, reusing all the same private helpers.
        // The output is already stored in the local $html var inside generate()
        // before it's echoed — we rebuild it here identically.
        ob_end_clean();
        $_GET    = $orig_get;
        $_SERVER = $orig_server;

        // ----- Re-run full HTML build (mirrors generate() exactly) -----
        $cfg = array(
            // Visibility toggles
            'show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,
            'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,
            'show_date'=>false,'show_site'=>false,'show_gallery'=>true,
            'show_section_scores'=>false,'show_audit_title'=>true,'show_flagged_summary'=>false,
            // Branding
            'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c',
            'logo_url'=>'','logo_position'=>'left',
            'report_title'=>'',
            // Footer
            'footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}',
            'footer_text'=>'',
            // Layout
            'page_margin'=>'normal',
            // Filename
            'pdf_filename'=>'{template}/{site}/{date}',
        );
        $t_cfg = $inspection->t_settings ? json_decode($inspection->t_settings, true) : array();
        if ( is_array($t_cfg) ) $cfg = array_merge($cfg, $t_cfg);

        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d ORDER BY sort_order", $inspection->template_id
        ));
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d", $inspection_id
        ));

        $rmap = array(); $child_rmap = array();
        foreach ($responses as $r) {
            $raw = $r->question_id;
            if ( is_numeric($raw) ) { $rmap[(int)$raw] = $r; }
            else {
                $ph = $r->photos ? json_decode($r->photos, true) : array();
                $r->photos = is_array($ph) ? array_values(array_filter(array_map(function($x){
                    return is_array($x) && !empty($x['url']) ? $x : null;
                }, $ph))) : array();
                $child_rmap[$raw] = $r;
            }
        }

        $items = array();
        foreach ($questions as $q) {
            $r  = isset($rmap[$q->id]) ? $rmap[$q->id] : null;
            $ph = $r && $r->photos ? json_decode($r->photos, true) : array();
            $ph = is_array($ph) ? array_values(array_filter(array_map(function($x){ return is_array($x)&&!empty($x['url'])?$x:null; },$ph))) : array();
            $logic = $q->logic ? json_decode($q->logic, true) : array();
            $items[] = (object)array(
                'id'=>$q->id,'label'=>$q->label,'type'=>$q->type,'section'=>$q->section?:'General',
                'value'=>$r?(string)$r->value:'','notes'=>$r?(string)$r->notes:'',
                'flagged'=>$r?(bool)$r->flagged:false,'photos'=>$ph,
                'logic'=>is_array($logic)?$logic:array(),
                'is_scored'=>$q->is_scored,'passing_answer'=>$q->passing_answer??'',
                'options'=>is_array(json_decode($q->options??'',true))?json_decode($q->options,true):(is_array($q->options??null)?$q->options:array()),
                'yes_no_colors'=>is_array(json_decode($q->yes_no_colors??'',true))?json_decode($q->yes_no_colors,true):array(),
            );
        }

        // Repeat map
        $repeat_rmap = array(); $sec_max_repeat = array();
        $qid_to_section = array();
        foreach ($questions as $q) { $qid_to_section[$q->id] = $q->section ?: 'General'; }
        foreach ($responses as $r) {
            $raw = $r->question_id;
            if ( preg_match('/^__r(\d+)__(.+)$/', $raw, $m) ) {
                $ri  = (int)$m[1]; $qid = $m[2];
                if ( !isset($repeat_rmap[$ri]) ) $repeat_rmap[$ri] = array();
                $ph = is_array($r->photos) ? $r->photos : ($r->photos ? json_decode($r->photos, true) : array());
                $r->photos = is_array($ph) ? array_values(array_filter(array_map(function($x){
                    return is_array($x) && !empty($x['url']) ? $x : null;
                }, $ph))) : array();
                $repeat_rmap[$ri][$qid] = $r;
                if ( isset($qid_to_section[$qid]) ) {
                    $sec = $qid_to_section[$qid];
                    if ( !isset($sec_max_repeat[$sec]) || $ri > $sec_max_repeat[$sec] ) $sec_max_repeat[$sec] = $ri;
                }
            }
        }
        ksort($repeat_rmap);

        $sections = array(); $section_order = array();
        foreach ($items as $item) {
            if ( $item->type==='signature' && empty($cfg['show_signature']) ) continue;
            $sec = $item->section ?: 'General';
            if ( !isset($sections[$sec]) ) { $sections[$sec] = array(); $section_order[] = $sec; }
            if ( $item->type === 'page' ) continue; // register section but exclude the page-break item itself
            $sections[$sec][] = $item;
        }

        $repeats_by_base = array();
        $rep_display_num  = array();
        foreach ($repeat_rmap as $ri => $qmap) {
            $rep_sections_at_ri = array();
            foreach ( $qmap as $qid => $dummy ) {
                if ( isset($qid_to_section[$qid]) ) $rep_sections_at_ri[ $qid_to_section[$qid] ] = true;
            }
            foreach ( $rep_sections_at_ri as $base_sec => $_ ) {
                // Check if THIS section's questions have actual data at this repeat index
                $sec_has_data = false;
                foreach ( $questions as $q ) {
                    if ( ($q->section ?: 'General') !== $base_sec ) continue;
                    $qid = (string)$q->id;
                    if ( ! isset($qmap[$qid]) ) continue;
                    $rr = $qmap[$qid];
                    $v  = isset($rr->value) ? (string)$rr->value : '';
                    if ( $v !== '' || ! empty($rr->notes) || ! empty($rr->photos) ) {
                        $sec_has_data = true; break;
                    }
                }
                if ( ! $sec_has_data ) continue;

                if ( !isset($repeats_by_base[$base_sec]) ) $repeats_by_base[$base_sec] = array();
                if ( !isset($rep_display_num[$base_sec]) ) $rep_display_num[$base_sec] = 1;
                $rep_display_num[$base_sec]++;
                $rep_sec = $base_sec . ' #' . $rep_display_num[$base_sec];
                // Build repeat-specific child_rmap: strip __r{ri}__ prefix
                $rep_prefix2 = '__r' . $ri . '__';
                $rep_child_rmap2 = array();
                foreach ( $child_rmap as $ck => $cv ) {
                    if ( strpos($ck, $rep_prefix2) === 0 ) {
                        $rep_child_rmap2[substr($ck, strlen($rep_prefix2))] = $cv;
                    }
                }
                // Also pull child_ entries already parsed into repeat_rmap
                if ( isset($repeat_rmap[$ri]) ) {
                    foreach ( $repeat_rmap[$ri] as $rk => $rv ) {
                        if ( strpos((string)$rk, 'child_') === 0 ) {
                            $rep_child_rmap2[$rk] = $rv;
                        }
                    }
                }

                $rep_items = array();
                foreach ( $questions as $q ) {
                    if ( $q->type === 'page' ) continue;
                    if ( $q->type==='signature' && empty($cfg['show_signature']) ) continue;
                    if ( ($q->section ?: 'General') !== $base_sec ) continue;
                    $qid   = (string)$q->id;
                    $r     = isset($qmap[$qid]) ? $qmap[$qid] : null;
                    $ph    = $r && $r->photos ? ( is_array($r->photos) ? $r->photos : json_decode($r->photos,true) ) : array();
                    $ph    = is_array($ph) ? array_values(array_filter(array_map(function($x){ return is_array($x)&&!empty($x['url'])?$x:null; },$ph))) : array();
                    $logic = $q->logic ? json_decode($q->logic, true) : array();
                    $rep_items[] = (object)array(
                        'id'=>$q->id,'label'=>$q->label,'type'=>$q->type,'section'=>$rep_sec,
                        'value'=>$r?(string)$r->value:'','notes'=>$r?(string)$r->notes:'',
                        'flagged'=>$r?(bool)$r->flagged:false,'photos'=>$ph,
                        'logic'=>is_array($logic)?$logic:array(),
                        'is_scored'=>$q->is_scored,'passing_answer'=>$q->passing_answer??'',
                        'options'=>is_array(json_decode($q->options??'',true))?json_decode($q->options,true):(is_array($q->options??null)?$q->options:array()),
                        'yes_no_colors'=>is_array(json_decode($q->yes_no_colors??'',true))?json_decode($q->yes_no_colors,true):array(),
                        '_child_rmap'=>$rep_child_rmap2,
                    );
                }
                $sections[$rep_sec] = $rep_items;
                $repeats_by_base[$base_sec][$ri] = $rep_sec;
            }
        }

        $new_section_order = array();
        foreach ( $section_order as $sec ) {
            $new_section_order[] = $sec;
            if ( isset($repeats_by_base[$sec]) ) {
                ksort($repeats_by_base[$sec]);
                foreach ( $repeats_by_base[$sec] as $ri => $rep_sec ) $new_section_order[] = $rep_sec;
            }
        }
        $section_order = $new_section_order;



        // ── Apply section show/hide conditions before building report rows ─────
        $wpi_get_resp_value = function($ref) use ($rmap) {
            $ref = (string)$ref;
            if ($ref === '') return '';
            if (is_numeric($ref) && isset($rmap[(int)$ref])) return (string)$rmap[(int)$ref]->value;
            foreach ($rmap as $qid => $rr) { if ((string)$qid === $ref) return (string)$rr->value; }
            return '';
        };
        $wpi_rule_matches = function($actual, $when) {
            $actual = trim((string)$actual);
            $when = is_array($when) ? (string)($when['label'] ?? $when['value'] ?? '') : (string)$when;
            if ($when === 'any') return true;
            if ($when === 'answered') return $actual !== '';
            if ($when === 'empty') return $actual === '';
            return $actual === trim($when);
        };
        $wpi_logic_section_vis = array();
        foreach ($items as $it) {
            if (empty($it->logic) || !is_array($it->logic)) continue;
            foreach ($it->logic as $rule) {
                if (empty($rule['section']) || empty($rule['action'])) continue;
                if ($rule['action'] !== 'show_section' && $rule['action'] !== 'hide_section') continue;
                $target = (string)$rule['section'];
                if (!isset($wpi_logic_section_vis[$target])) $wpi_logic_section_vis[$target] = array('hasShow'=>false,'showMatched'=>false,'hideMatched'=>false);
                $matched = $wpi_rule_matches($it->value ?? '', $rule['when'] ?? '');
                if ($rule['action'] === 'show_section') { $wpi_logic_section_vis[$target]['hasShow'] = true; if ($matched) $wpi_logic_section_vis[$target]['showMatched'] = true; }
                if ($rule['action'] === 'hide_section' && $matched) $wpi_logic_section_vis[$target]['hideMatched'] = true;
            }
        }
        $wpi_should_show_section = function($sec_name) use ($cfg, $wpi_get_resp_value, $wpi_logic_section_vis) {
            $base_sec = preg_replace('/\s+#\d+$/', '', (string)$sec_name);
            $conds = isset($cfg['section_conditions']) && is_array($cfg['section_conditions']) ? $cfg['section_conditions'] : array();
            $cond = $conds[$sec_name] ?? ($conds[$base_sec] ?? null);
            if (is_array($cond) && (!empty($cond['question_db_id']) || !empty($cond['question_id']) || !empty($cond['question_key'])) && isset($cond['value']) && $cond['value'] !== '') {
                $refs = array($cond['question_db_id'] ?? '', $cond['question_id'] ?? '', $cond['question_key'] ?? '');
                $cval = '';
                foreach ($refs as $ref) { if ($ref === '') continue; $cval = $wpi_get_resp_value($ref); if ($cval !== '') break; }
                $match = trim((string)$cval) === trim((string)$cond['value']);
                $mode = isset($cond['mode']) ? (string)$cond['mode'] : 'show';
                if ($mode === 'hide') { if ($match) return false; }
                else { if (!$match) return false; }
            }
            $v = $wpi_logic_section_vis[$sec_name] ?? ($wpi_logic_section_vis[$base_sec] ?? null);
            if (is_array($v)) { if (!empty($v['hideMatched'])) return false; if (!empty($v['hasShow']) && empty($v['showMatched'])) return false; }
            return true;
        };
        $section_order = array_values(array_filter($section_order, function($sec_name) use ($wpi_should_show_section, &$sections) {
            if (!$wpi_should_show_section($sec_name)) { unset($sections[$sec_name]); return false; }
            return true;
        }));
        $items = array_values(array_filter($items, function($it) use ($wpi_should_show_section) { return $wpi_should_show_section($it->section ?: 'General'); }));

        // Photos
        $all_photos = array();
        foreach ($section_order as $sec_name) {
            if ( !isset($sections[$sec_name]) ) continue;
            foreach ($sections[$sec_name] as $item) {
                foreach ($item->photos as $ph) { $url=$ph['url']??''; if($url) $all_photos[]=array('url'=>$url,'label'=>$item->label,'section'=>$sec_name); }
                foreach (self::resolve_children($item, (isset($item->_child_rmap)&&is_array($item->_child_rmap)?$item->_child_rmap:$child_rmap), 0) as $child) {
                    foreach ($child->photos as $ph) { $url=$ph['url']??''; if($url) $all_photos[]=array('url'=>$url,'label'=>$child->label,'section'=>$sec_name); }
                }
            }
        }

        $embedded = array(); $embedded_full = array();
        foreach ($all_photos as $ph) {
            $url = $ph['url'];
            if (!isset($embedded[$url]))      $embedded[$url]      = self::embed_image($url, 160, 120, 60);
            if (!isset($embedded_full[$url])) $embedded_full[$url] = self::embed_image($url, 600, 450, 65);
        }

        // Stats
        $yes_count  = count(array_filter($items,function($i){return strtolower($i->value)==='yes';}));
        $no_count   = count(array_filter($items,function($i){return strtolower($i->value)==='no';}));
        $na_count   = count(array_filter($items,function($i){return strtolower($i->value)==='n/a';}));
        $flag_count = count(array_filter($items,function($i){return $i->flagged;}));
        $total_q    = count(array_filter($items,function($i){return !in_array($i->type,array('instruction','page'));}));
        $answered_q = count(array_filter($items,function($i){ return !in_array($i->type,array('instruction','page'))&&($i->value!==''||count($i->photos)>0); }));

        $score       = $inspection->score!==null ? round((float)$inspection->score) : null;
        $score_str   = $score!==null ? 'Score '.$score.'%' : 'N/A';
        $score_color = $score===null ? '#6b7280' : ($score>=80 ? '#16a34a' : ($score>=50 ? '#d97706' : '#dc2626'));
        $score_bg    = $score===null ? '#f3f4f6' : ($score>=80 ? '#dcfce7' : ($score>=50 ? '#fef3c7' : '#fee2e2'));

        $is_complete  = strtolower($inspection->status??'') === 'complete';
        $status       = $is_complete ? 'Complete' : ucfirst($inspection->status??'In Progress');
        $status_color = $is_complete ? '#16a34a' : '#d97706';

        $wp_tz_string = get_option('timezone_string') ?: '';
        if ( !$wp_tz_string ) {
            $offset = (float) get_option('gmt_offset', 0);
            $sign   = $offset >= 0 ? '+' : '-';
            $abs    = abs($offset);
            $h      = (int)$abs;
            $m      = (int)(($abs - $h) * 60);
            $wp_tz_string = sprintf('UTC%s%02d:%02d', $sign, $h, $m);
        }
        try { $wp_tz = new DateTimeZone($wp_tz_string); } catch(Exception $e) { $wp_tz = new DateTimeZone('UTC'); }

        $raw_dt   = $inspection->conducted_at ?? $inspection->created_at ?? '';
        $date_str = ''; $time_str = ''; $tz_abbr = '';
        if ( $raw_dt ) {
            try {
                $dt           = new DateTime($raw_dt, $wp_tz);
                $date_str     = $dt->format('d M Y');
                $time_str     = $dt->format('g:i A');
                $tz_trans     = $wp_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
                $abbr_raw     = !empty($tz_trans[0]['abbr']) ? $tz_trans[0]['abbr'] : '';
                $tz_abbr      = ($abbr_raw && $abbr_raw !== 'UTC') ? $abbr_raw : '';
            } catch(Exception $e) {
                $parts    = preg_split('/[\s\-:T]/', $raw_dt);
                $date_str = self::format_display_datetime( $raw_dt );
                $time_str = '';
            }
        }
        $site = $inspection->site_name ?: '';

        $header_col   = '#ffffff';
        $header_text  = '#000000';
        $accent_col   = !empty($cfg['accent_color']) ? $cfg['accent_color'] : $header_col;
        $logo_pos     = $cfg['logo_position'] ?? 'left';
        $pm           = $cfg['page_margin'] ?? 'normal';
        $page_margin_css = $pm === 'narrow' ? '8mm 8mm 12mm 8mm'
                         : ($pm === 'wide'  ? '20mm 18mm 20mm 18mm'
                                            : '14mm 12mm 14mm 12mm');
        $footer_left_raw   = $cfg['footer_left']    ?? '{template}';
        $footer_center_raw = $cfg['footer_center']   ?? '';
        $footer_right_raw  = $cfg['footer_right']    ?? 'Page {page} of {pages}';
        $footer_text_legacy = $cfg['footer_text']   ?: '';
        $footer_text  = $footer_text_legacy ?: 'Private &amp; confidential';
        $flagged_summary_html = '';

        // Resolve report title tokens
        $raw_title_tpl = trim($cfg['report_title'] ?? '');
        $q_map_t = array();
        foreach ( $items as $item ) {
            if ( in_array($item->type, array('instruction','page')) ) continue;
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i','_', $item->label ?? ''));
            $slug = trim($slug,'_'); $slug = substr($slug,0,40);
            if ( $slug && isset($rmap[$item->id]) ) $q_map_t[$slug] = self::format_token_value_by_type( $rmap[$item->id]->value, $item->type ?? '' );
        }
        if ( empty($q_map_t['site']) && !empty($inspection->site_name) ) {
            $q_map_t['site'] = $inspection->site_name;
        }
        if ( $raw_title_tpl ) {
            $dt_t = !empty($inspection->conducted_at) ? new DateTime($inspection->conducted_at) : new DateTime();
            $res  = $raw_title_tpl;
            $res  = str_replace('{date}',      $dt_t->format('d M Y'), $res);
            $res  = str_replace('{time}',      $dt_t->format('g:i A'), $res);
            $res  = str_replace('{template}',  $inspection->template_title ?? $inspection->title, $res);
            $res  = str_replace('{site}',      $inspection->site_name ?: '', $res);
            $res  = str_replace('{inspector}', $inspection->inspector_name ?? '', $res);
            $res  = str_replace('{score}',     ($inspection->score!==null ? round($inspection->score).'%' : ''), $res);
            $res  = preg_replace_callback('/\{field:([^}]+)\}/', function($m) use ($q_map_t) {
                $t = trim($m[1]);
                if ( isset($q_map_t[$t]) ) return $q_map_t[$t];
                foreach ( $q_map_t as $k => $v ) { if ( strpos($k,$t)===0 ) return $v; }
                return '';
            }, $res);
            $doc_title = trim(preg_replace('/\s+/', ' ', $res)) ?: $inspection->title;
        } else {
            $doc_title = $inspection->title;
        }
        $report_title = $doc_title;

        $save_name = $report_title;
        $save_name = preg_replace( '/[\x00-\x1f\x7f]/u', '', $save_name );
        $save_name = str_replace( array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '-', $save_name );
        $save_name = trim( preg_replace( '/\s+/', ' ', $save_name ) ) ?: 'Inspection Report';

        $logo_html = '';
        if ( !empty($cfg['logo_url']) ) {
            $logo_src  = self::embed_logo($cfg['logo_url']);
            $logo_align = $logo_pos === 'right' ? 'margin-left:auto;margin-right:0;'
                        : ($logo_pos === 'center' ? 'margin-left:auto;margin-right:auto;' : '');
            $logo_html = '<img src="'.esc_attr($logo_src).'" class="logo-img" style="'.esc_attr($logo_align).'">';
        }

        // Photo number map
        $photo_num_map = array();
        foreach ($all_photos as $gi => $gph) {
            $url = $gph['url'];
            if (!isset($photo_num_map[$url])) $photo_num_map[$url] = $gi + 1;
        }

        // Section rows
        $body_html = '';
        foreach ($section_order as $sec_name) {
            $sec_items = $sections[$sec_name];
            $force_section_new_page = (!empty($sec_items) && isset($sec_items[0]->type) && $sec_items[0]->type === 'page');
            if ($force_section_new_page) {
                while (!empty($sec_items) && isset($sec_items[0]->type) && $sec_items[0]->type === 'page') {
                    array_shift($sec_items);
                }
            }
            $section_has_sig = !empty(array_filter($sec_items,function($i){return $i->type==='signature';}));
            if ($section_has_sig && !$force_section_new_page) { $force_section_new_page = true; }
            $section_classes = 'section' . ($force_section_new_page ? ' section-start-new-page' : '');
            $section_style = ''; // page breaks handled by CSS only
            $s_scored_total = count(array_filter($sec_items,function($i){
                return $i->is_scored!==null && !in_array($i->type,array('instruction','page','signature'));
            }));
            $s_score_str = '';
            if ($s_scored_total > 0) {
                $s_passed = count(array_filter($sec_items,function($i){
                    if ($i->is_scored===null||$i->value===''||$i->is_scored==0) return false;
                    $pa = strtolower(trim($i->passing_answer??''));
                    return $pa===''||$pa==='any'||$pa===strtolower(trim($i->value));
                }));
                $s_score_str = round($s_passed/$s_scored_total*100).'%';
            }

            $body_html .= '<div class="'.esc_attr($section_classes).'"'.($section_style ? ' style="'.esc_attr($section_style).'"' : '').'><div class="section-header"><span class="section-name">'.esc_html($sec_name).'</span>'
                . ((!empty($cfg['show_section_scores']) && $s_score_str) ? '<span class="section-score">'.esc_html($s_score_str).'</span>' : '')
                . '</div>';

            foreach ($sec_items as $item) {
                $vl       = strtolower(trim($item->value));
                $item_child_rmap2 = isset($item->_child_rmap) && is_array($item->_child_rmap) ? $item->_child_rmap : $child_rmap;
                $children = self::resolve_children($item, $item_child_rmap2, 0);
                if ($item->type === 'page') {
                    $body_html .= '<div class="page-break template-page-break"></div>';
                    continue;
                }
                if ($item->type === 'instruction') {
                    $body_html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:4px 0;">'
                        . '<tr>'
                        . '<td bgcolor="#eff6ff" style="background-color:#eff6ff;border-left:4px solid #3b82f6;border-top:1px solid #bfdbfe;border-bottom:1px solid #bfdbfe;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">'
                        . '<div style="font-size:10px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">&#9432; INFORMATION</div>'
                        . '<div style="font-size:12px;color:#1e3a8a;line-height:1.7;">'.nl2br(esc_html($item->label)).'</div>'
                        . '</td>'
                        . '</tr>'
                        . '</table>';
                    continue;
                }

                $val_html  = self::response_value($item, $vl, $embedded);
                $flag_html = $item->flagged ? '<span class="flag-pill">⚑ Flagged</span>' : '';
                $note_html = '';
                if ($item->notes)
                    $note_html = '<div class="row-note" style="white-space:pre-wrap">'.nl2br(esc_html($item->notes)).'</div>';

                $photos_html = '';
                if (!empty($cfg['show_photos']) && count($item->photos) > 0) {
                    $photos_html = '<div class="photo-strip">';
                    foreach ($item->photos as $idx => $ph) {
                        $url = $ph['url']??''; if (!$url) continue;
                        $src = isset($embedded[$url]) ? $embedded[$url] : $url;
                        $pn  = isset($photo_num_map[$url]) ? $photo_num_map[$url] : ($idx+1);
                        $photos_html .= '<div class="photo-thumb"><img src="'.esc_attr($src).'" alt="Photo '.$pn.'"><span class="photo-num">Photo '.$pn.'</span></div>';
                    }
                    $photos_html .= '</div>';
                }

                $sig_html = '';
                if ($item->type === 'signature' && $item->value) {
                    $decoded  = json_decode($item->value, true);
                    $sig_name = is_array($decoded) ? ($decoded['name']??'') : '';
                    $sig_date = $date_str.' '.$time_str.' '.$tz_abbr;
                    $sig_data = is_array($decoded) ? ($decoded['sig']??'') : (strpos($item->value,'data:')===0?$item->value:'');
                    if ($sig_data || $sig_name) {
                        $sig_html = '<div class="sig-block">';
                        if ($sig_data) $sig_html .= '<img src="'.esc_attr($sig_data).'" class="sig-img">';
                        $sig_html .= '<div class="sig-meta">';
                        if ($sig_name) $sig_html .= '<span class="sig-name">'.esc_html($sig_name).'</span>';
                        $sig_html .= '<span class="sig-date">'.esc_html($sig_date).'</span>';
                        $sig_html .= '</div></div>';
                    }
                }

                $is_long = ($item->type === 'textarea' || $item->type === 'long_text' || $item->type === 'short_text')
                    || (strlen(wp_strip_all_tags((string)$item->value)) > 70);

                $row_extra_class = ($item->type === 'signature') ? ' row-sig' : '';
                $is_chip2 = in_array($item->type, array('yes_no','multiple_choice','select','dropdown','checkbox','radio','multi_select'));

                $children_html2 = '';
                foreach ($children as $child) {
                    $cvl        = strtolower(trim($child->value));
                    $child_val  = self::response_value($child, $cvl, $embedded);
                    $child_note = $child->notes ? '<div class="row-note" style="white-space:pre-wrap">'.nl2br(esc_html($child->notes)).'</div>' : '';
                    $child_photos = '';
                    if (!empty($cfg['show_photos']) && count($child->photos)>0) {
                        $child_photos = '<div class="photo-strip">';
                        foreach ($child->photos as $ci => $cph) {
                            $curl = $cph['url']??''; if (!$curl) continue;
                            $csrc = isset($embedded[$curl]) ? $embedded[$curl] : $curl;
                            $cpn  = isset($photo_num_map[$curl]) ? $photo_num_map[$curl] : ($ci+1);
                            $child_photos .= '<div class="photo-thumb"><img src="'.esc_attr($csrc).'" alt="Photo '.$cpn.'"><span class="photo-num">Photo '.$cpn.'</span></div>';
                        }
                        $child_photos .= '</div>';
                    }
                    $children_html2 .= '<div class="child-row">'
                        . '<div class="child-label">↳ '.esc_html($child->label).'</div>'
                        . '<div class="child-value">'.$child_val.'</div>'
                        . $child_note.$child_photos.'</div>';
                }

                if (!$is_long && $is_chip2) {
                    $chip2 = self::chip_data($item, $vl);
                    $chip_bg2  = $chip2 ? esc_attr($chip2['bg']) : '#e5e7eb';
                    $chip_fg2  = $chip2 ? esc_attr($chip2['fg']) : '#374151';
                    $chip_txt2 = $chip2 ? esc_html($chip2['label']) : esc_html($item->value);
                    $body_html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;border-bottom:1px solid #f1f5f9;">'
                        . '<tr>'
                        . '<td style="font-size:13px;color:#1a1a2e;font-weight:700;font-family:Arial,Helvetica,sans-serif;padding:11px 8px 11px 20px;width:60%;vertical-align:middle;">'.esc_html($item->label).'</td>'
                        . '<td bgcolor="'.$chip_bg2.'" style="background:'.$chip_bg2.';color:'.$chip_fg2.';font-size:13px;font-weight:700;padding:10px 20px;text-align:right;vertical-align:middle;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;">'.$chip_txt2.$flag_html.'</td>'
                        . '</tr>'
                        . ($note_html   ? '<tr><td colspan="2" style="padding:2px 20px 8px 20px;">'.$note_html.'</td></tr>' : '')
                        . ($photos_html ? '<tr><td colspan="2" style="padding:2px 20px 8px 20px;">'.$photos_html.'</td></tr>' : '')
                        . '</table>';
                        $body_html .= $children_html2;
                } else {
                    $body_html .= '<div class="row'.($item->flagged?' row-flagged':'').$row_extra_class.'">';
                    $body_html .= '<div class="row-label">'.esc_html($item->label).'</div>';
                    $body_html .= $is_long
                        ? '<div class="row-value-block">'.$val_html.$flag_html.'</div>'
                        : '<div class="row-value">'.$val_html.$flag_html.'</div>';
                    $body_html .= $note_html.$photos_html.$children_html2;
                    if ($sig_html) {
                        $body_html .= '<div class="sig-row-inner">' . $sig_html . '</div>';
                    }
                    $body_html .= '</div>'; // .row
                }
            }
            $body_html .= '</div>'; // .section
        }
        // Resolve header toggle booleans via cfg_on (handles "0", "false", absent keys correctly)
        $show_audit_title   = self::cfg_on($cfg, 'show_audit_title', true);
        $show_site_hdr      = self::cfg_on($cfg, 'show_site', false);
        $show_date_hdr      = self::cfg_on($cfg, 'show_date', false);
        $show_inspector_hdr = self::cfg_on($cfg, 'show_inspector', false);
        $show_score_hdr     = self::cfg_on($cfg, 'show_score', false);
        $show_summary_bar   = self::cfg_on($cfg, 'show_summary', false);
        $show_gallery_pg    = self::cfg_on($cfg, 'show_gallery', true);

        // Flagged summary
        $flagged_summary_html = '';
        if (!empty($cfg['show_flagged_summary'])) {
            $fi2 = array_filter($items, function($item){ return $item->flagged; });
            if (count($fi2) > 0) {
                $flagged_summary_html = '<div class="flagged-summary"><div class="flagged-summary-header">⚑ Flagged Items Summary ('.count($fi2).')</div>';
                foreach ($fi2 as $fi) {
                    $fvl = strtolower(trim($fi->value));
                    $fval = self::response_value($fi, $fvl, $embedded);
                    $flagged_summary_html .= '<div class="flagged-row">'
                        . '<div class="flagged-section">'.esc_html($fi->section).'</div>'
                        . '<div class="flagged-label">'.esc_html($fi->label).'</div>'
                        . '<div class="flagged-value">'.$fval.'</div>'
                        . ($fi->notes ? '<div class="flagged-note" style="white-space:pre-wrap">'.nl2br(esc_html($fi->notes)).'</div>' : '')
                        . '</div>';
                }
                $flagged_summary_html .= '</div>';
            }
        }

        // Auditor notes
        $notes_html = '';
        if (!empty($inspection->notes) && self::cfg_on($cfg, 'show_notes', true)) {
            $notes_html = '<div class="auditor-notes"><div class="notes-header">Auditor Notes</div>'
                . '<div class="notes-text">'.nl2br(esc_html($inspection->notes)).'</div></div>';
        }

        // Gallery
        $gallery_html = '';
        if ($show_gallery_pg && count($all_photos) > 0) {
            $logo_in_gallery = !empty($cfg['logo_url'])
                ? '<img src="'.esc_attr(self::embed_logo($cfg['logo_url'])).'" class="logo-img" style="max-height:40px;max-width:120px;">'
                : '';
            $img_num = 1;
            $chunks = array_chunk($all_photos, 4);
            foreach ($chunks as $ci => $chunk) {
                $title = $ci === 0 ? 'Media Summary' : 'Media Summary (continued)';
                $gallery_html .= '<div class="gallery-page-break"></div><div class="gallery-page">'
                    . '<div class="gallery-header"><span class="gallery-title">'.$title.'</span>'.$logo_in_gallery.'</div>'
                    . '<div class="gallery-grid">';
                $cells = array_values($chunk);
                while (count($cells) < 4) { $cells[] = null; }
                foreach ($cells as $ph) {
                    if ($ph) {
                        $url  = $ph['url'];
                        $fsrc = isset($embedded_full[$url]) ? $embedded_full[$url] : $url;
                        $gallery_html .= '<div class="gallery-cell"><div class="gallery-img-wrap">'
                            . '<img src="'.esc_attr($fsrc).'" alt="Photo '.$img_num.'" onerror="this.parentNode.innerHTML=\'&lt;div class=gallery-err&gt;Photo unavailable&lt;/div&gt;\'">'
                            . '</div><div class="gallery-cap"><strong>Photo '.$img_num.'</strong></div></div>';
                        $img_num++;
                    } else {
                        $gallery_html .= '<div class="gallery-cell gallery-cell-empty"><div class="gallery-img-wrap gallery-img-wrap-empty"></div><div class="gallery-cap gallery-cap-empty">&nbsp;</div></div>';
                    }
                }
                $gallery_html .= '</div></div>';
            }
        }

        // Meta rows — respect header settings toggles
        $meta_rows = array();
        if ( $inspection->template_title && $show_audit_title ) $meta_rows[] = array('Audit Title', $inspection->template_title);
        if ( $site && $show_site_hdr ) $meta_rows[] = array('Site', $site);
        if ( $show_date_hdr ) $meta_rows[] = array('Conducted on', trim($date_str.' '.$time_str.' '.$tz_abbr));
        if ( !empty($inspection->inspector_name) && $show_inspector_hdr ) $meta_rows[] = array('Prepared by', $inspection->inspector_name);
        $meta_html = '';
        foreach ($meta_rows as $mr) {
            $meta_html .= '<div class="meta-row"><span class="meta-label">'.esc_html($mr[0]).':</span><span class="meta-value">'.esc_html($mr[1]).'</span></div>';
        }

        // Summary bar — respect show_summary and show_score toggles
        $summary_html = '';
        if ( $show_summary_bar ) {
            $summary_html = '<div class="summary-bar">'
                . '<div class="summary-breadcrumb">'.esc_html($site ?: $report_title).' / '.esc_html($inspection->template_title).' / '.esc_html($date_str)
                . ' <span class="summary-status" style="color:'.$status_color.'">'.esc_html($status).'</span></div>'
                . '<div class="summary-stats">';
            if ( $show_score_hdr )
                $summary_html .= '<div class="sum-cell"><span class="sum-num">'.esc_html($score_str).'</span><span class="sum-label">Score</span></div>';
            $summary_html .= '<div class="sum-cell"><span class="sum-num">'.esc_html($flag_count).'</span><span class="sum-label">Flagged items</span></div>'
                . '<div class="sum-cell"><span class="sum-num">0</span><span class="sum-label">Actions</span></div>'
                . '</div></div>';
        }

        // CSS — same as generate(), minus screen-only print-bar rules
        $css = '
*{box-sizing:border-box;margin:0;padding:0;}
h1,h2,h3,h4,h5,h6,p{margin:0;padding:0;font-size:13px;font-weight:400;font-family:Arial,Helvetica,sans-serif;}body{font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:400;color:#1a1a1a;background:#fff;}
.page{max-width:820px;margin:0 auto;background:#fff;}
.logo-img{max-height:52px;max-width:160px;object-fit:contain;display:block;margin-bottom:18px;}
.header{background:'.esc_attr($header_col).';padding:24px 28px 20px;}
.header-label{font-size:11px;font-weight:700;color:'.esc_attr($header_text).';opacity:.65;letter-spacing:.8px;text-transform:uppercase;margin-bottom:6px;}
.header-title{font-size:22px;font-weight:800;color:'.esc_attr($header_text).';line-height:1.25;}
.header-subtitle{font-size:12px;color:'.esc_attr($header_text).';opacity:.75;margin-top:6px;}
.summary-bar{border-bottom:1px solid #dbe2ea;padding:0 28px 14px;background:#fff;}
.summary-breadcrumb{font-size:13px;color:#374151;margin-bottom:14px;line-height:1.5;display:flex;justify-content:space-between;gap:12px;align-items:flex-start;}
.summary-status{font-weight:800;white-space:nowrap;}
.summary-stats{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid #dbe2ea;background:#fff;}
.sum-cell{display:flex;flex-direction:column;gap:2px;padding:14px 20px;border-right:1px solid #dbe2ea;}
.sum-cell:last-child{border-right:none;}
.sum-num{font-size:22px;font-weight:800;color:#111;}
.sum-label{font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
.meta-section{border-bottom:2px solid #e5e7eb;}
.meta-row{display:grid;grid-template-columns:180px 1fr;padding:11px 20px;border-bottom:1px solid #f3f4f6;align-items:baseline;}
.meta-row:last-child{border-bottom:none;}
.meta-label{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;}
.meta-value{font-size:13px;color:#111;font-weight:500;text-align:right;}
.section{margin-bottom:12px;}
.section-header{display:flex;align-items:center;justify-content:space-between;background:#E9ECF7;padding:10px 16px;border-top:1px solid #c5cae9;border-bottom:1px solid #c5cae9;}
.section-name{font-size:13px;font-weight:800;color:#1a1a2e;}
.section-score{font-size:11px;font-weight:700;color:#4b5563;opacity:1;}
.row{display:grid;grid-template-columns:180px 1fr;column-gap:16px;row-gap:0;padding:11px 20px;border-bottom:1px solid #f1f5f9;align-items:center;orphans:2;widows:2;}
.row:last-child{border-bottom:none;}
.row-flagged{background:#fffbeb;}
.row-label{font-size:13px;color:#1a1a2e;font-weight:700;line-height:1.4;grid-column:1;grid-row:1;font-family:Arial,Helvetica,sans-serif;}
.row-value{grid-column:2;grid-row:1;text-align:right;font-size:13px;font-weight:400;color:#374151;line-height:1.5;word-break:break-word;vertical-align:middle;}.chip-row{border-bottom:1px solid #f1f5f9;border-collapse:collapse;break-inside:avoid;page-break-inside:avoid;}.chip-row-label{font-size:13px;color:#1a1a2e;font-weight:700;line-height:1.4;font-family:Arial,Helvetica,sans-serif;padding:11px 8px 11px 20px;width:55%;vertical-align:middle;}.chip-row-value{padding:8px 20px 8px 8px;text-align:right;vertical-align:middle;}.answer-chip{display:inline-block;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.row-value-block{grid-column:1/-1;margin-top:4px;font-size:13px;font-weight:400;color:#374151;line-height:1.6;white-space:pre-wrap;word-break:break-word;font-family:Arial,Helvetica,sans-serif;}.row-value-block .val-text{font-size:13px!important;font-weight:400!important;line-height:1.6;color:#111;text-align:left!important;max-width:none!important;display:block;}
.row-note{grid-column:1/-1;margin-top:6px;font-size:11px;color:#6b7280;background:#f9fafb;padding:6px 10px;border-radius:4px;border-left:2px solid #e5e7eb;}.row-note-required{color:#5b21b6;background:#f5f3ff;border-left:2px solid #8b5cf6;}
.photo-strip{grid-column:1/-1;display:grid;grid-template-columns:repeat(10,1fr);gap:3px;margin-top:6px;}
.photo-thumb{display:flex;flex-direction:column;align-items:center;gap:3px;}
.photo-thumb img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:3px;border:1px solid #e5e7eb;display:block;}
.photo-num{font-size:8px;color:#9ca3af;font-weight:600;}
.row-instruction{display:block;width:100%;box-sizing:border-box;font-size:12px;color:#1e3a8a;background:#eff6ff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;border-left:4px solid #3b82f6;border-top:1px solid #bfdbfe;border-bottom:1px solid #bfdbfe;padding:10px 16px;white-space:pre-wrap;word-wrap:break-word;line-height:1.7;margin:2px 0;}.row-instruction .info-label,.row-instruction strong.info-label{font-size:10px;font-weight:700;color:#1d4ed8!important;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px;}@media print{.row-instruction{background:#eff6ff!important;color:#1e3a8a!important;border-left:4px solid #3b82f6!important;}}
.child-row{grid-column:1/-1;display:block;padding:8px 20px 8px 36px;border-bottom:1px solid #f8fafc;background:#fafafa;break-inside:avoid;page-break-inside:avoid;border-left:3px solid '.esc_attr($accent_col).';margin:0 -20px;}
.child-label{font-size:11px;color:#6b7280;line-height:1.4;margin-bottom:3px;font-style:italic;}
.child-value{font-size:13px;color:#111;line-height:1.5;word-break:break-word;overflow-wrap:break-word;font-weight:500;}
.chip-yes{display:inline-flex;align-items:center;gap:4px;background:#15803d;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-no{display:inline-flex;align-items:center;gap:4px;background:#dc2626;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-na{display:inline-flex;align-items:center;gap:4px;background:#6b7280;color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-signed{display:inline-flex;align-items:center;gap:4px;background:#15803d;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:5px;white-space:nowrap;}
.chip-mc{display:inline-block;font-size:12px;font-weight:700;padding:4px 12px;border-radius:5px;white-space:nowrap;line-height:1.4;vertical-align:middle;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.val-text{font-size:13px;font-weight:400;color:#111;text-align:right;line-height:1.4;word-break:break-word;white-space:pre-wrap;font-family:Arial,Helvetica,sans-serif;overflow-wrap:break-word;display:block;}
.val-photo{font-size:12px;color:#374151;font-weight:600;}
.val-empty{font-size:13px;color:#d1d5db;}
.val-rating{font-size:15px;color:#f59e0b;letter-spacing:2px;}
.flag-pill{background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;white-space:nowrap;}
.sig-row-inner{grid-column:1/-1;break-inside:avoid;page-break-inside:avoid;}.sig-block{padding:16px 20px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;margin-top:8px;display:inline-block;max-width:340px;break-inside:avoid;page-break-inside:avoid;}
.sig-img{display:block;width:300px;height:130px;border:1.5px solid #d1d5db;border-radius:6px;object-fit:contain;background:#fff;margin-bottom:8px;}
.sig-meta{display:flex;flex-direction:column;gap:2px;border-top:1px solid #e5e7eb;padding-top:8px;}
.sig-name{font-size:13px;font-weight:700;color:#111;}
.sig-date{font-size:11px;color:#6b7280;}
.auditor-notes{padding:16px 20px;background:#fffbeb;border-top:2px solid #fde68a;}
.notes-header{font-size:11px;font-weight:700;color:#92400e;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
.notes-text{font-size:12px;color:#555;line-height:1.6;}
.content-flow{padding-bottom:'.esc_attr($content_gap_css).';}
.footer{text-align:center;padding:14px 20px;font-size:10px;color:#9ca3af;border-top:1px solid #f1f5f9;background:#fff;}
.page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;border:none;margin:0;padding:0;}.template-page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;line-height:1px;}
.gallery-page-break{display:block;page-break-before:always;}
.gallery-page{max-width:820px;margin:0 auto;padding:34px 24px 20px;background:#fff;}
.gallery-header{display:flex;align-items:center;justify-content:space-between;padding-top:10px;padding-bottom:8px;border-bottom:2px solid #e5e7eb;margin-bottom:10px;min-height:56px;}
.gallery-title{font-size:15px;font-weight:800;color:#111;}
.gallery-chunk{margin-bottom:0;}
.gallery-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:stretch;margin-bottom:0;break-inside:avoid;page-break-inside:avoid;}
.gallery-cell{display:flex;flex-direction:column;gap:8px;min-width:0;}
.gallery-img-wrap{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;aspect-ratio:4/3;padding:10px;}
.gallery-img-wrap img{width:100%;height:100%;object-fit:contain;display:block;}
.gallery-page-divider{display:flex;align-items:center;justify-content:space-between;padding:16px 0 8px;border-top:2px solid #e5e7eb;margin-top:4px;}
.gallery-cap{font-size:10px;color:#6b7280;padding:0 2px;min-height:24px;text-align:center;}
.gallery-cap strong{color:#374151;display:block;font-size:11px;} .gallery-cell-empty{visibility:hidden;} .gallery-img-wrap-empty{background:transparent;border:1px dashed transparent;} .gallery-cap-empty{visibility:hidden;} .gallery-err{display:flex;align-items:center;justify-content:center;height:100%;width:100%;font-size:11px;color:#6b7280;text-align:center;padding:12px;}
@page{margin:'.esc_attr($page_margin_css).';}@media print{body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.page{box-shadow:none;max-width:100%;margin:0;border-radius:0;}.content-flow{padding-bottom:0;}.footer{position:fixed;left:0;right:0;bottom:0;padding:'.esc_attr($footer_pad_css).';min-height:12mm;background:#fff;z-index:20;}.print-bar{display:none!important;}.page-break{display:block !important;page-break-before:always !important;break-before:page !important;height:1px;}.gallery-page-break{page-break-before:always;}.gallery-page{break-inside:avoid;page-break-inside:avoid;padding-top:34px;}.section{display:block;break-inside:auto;page-break-inside:auto;}.section.section-start-new-page{display:block;}.section-header{break-after:avoid;page-break-after:avoid;break-inside:avoid;page-break-inside:avoid;margin-top:8px;}.row{break-inside:avoid;page-break-inside:avoid;}.row-sig{break-inside:auto;page-break-inside:auto;}.row-sig .sig-row-inner{break-before:avoid;page-break-before:avoid;}.sig-row-inner{break-inside:avoid;page-break-inside:avoid;}.sig-block{break-inside:avoid;page-break-inside:avoid;}.sig-meta{break-inside:avoid;page-break-inside:avoid;}.header{break-inside:avoid;page-break-inside:avoid;}}
';

        $show_header_block = !empty($cfg['logo_url']) || !empty($cfg['report_title']) || $show_summary_bar || $meta_html;
        $header_block = '';
        if ( $show_header_block ) {
            $header_block = '<div class="header"><div class="header-top"><div class="header-brand">'
                . $logo_html
                . ($show_audit_title ? '<div class="header-title">'.esc_html($report_title).'</div>' : '')
                . ( ($show_site_hdr || $show_date_hdr)
                    ? '<div class="header-subtitle">'.esc_html(trim($site.($site && $inspection->template_title ? ' / ' : '').($inspection->template_title ?: '').' / '.$date_str)).'</div>'
                    : '' )
                . '</div></div></div>';
        }

        $html = '<!DOCTYPE html><html lang="en"><head>'
            . '<title>'.esc_html($save_name).'</title>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>'.$css.'</style>'
            . '</head><body>'
            . '<div class="page"><div class="content-flow">'
            . $header_block
            . $summary_html
            . ($meta_html ? '<div class="meta-section">'.$meta_html.'</div>' : '')
            . $body_html
            . $flagged_summary_html
            . $notes_html
            . '</div>'
            . '<div class="footer"><div class="footer-inner">'
            . '<span class="footer-left">'.esc_html(self::resolve_footer_tokens($footer_left_raw ?: $footer_text_legacy, $inspection, $site, $date_str)).'</span>'
            . '<span class="footer-center">'.esc_html(self::resolve_footer_tokens($footer_center_raw, $inspection, $site, $date_str)).'</span>'
            . '<span class="footer-right">'.esc_html(self::resolve_footer_tokens($footer_right_raw, $inspection, $site, $date_str)).'</span>'
            . '</div></div>'
            . '</div>'
            . ($show_gallery_pg ? $gallery_html : '')
            . '</body></html>';

        return $html;
    }

    /**
     * Convert a rich HTML string to a PDF file via wkhtmltopdf.
     * Returns the temp file path on success, null on failure.
     * Caller must unlink() the file after use.
     */
    private static function html_to_pdf( $html, $inspection_id, $save_name = 'report', $cfg = array(), $inspection = null, $site = '', $date_str = '' ) {
        // Locate wkhtmltopdf binary
        $bin = '';
        foreach ( array( '/usr/bin/wkhtmltopdf', '/usr/local/bin/wkhtmltopdf', '/opt/bin/wkhtmltopdf' ) as $candidate ) {
            if ( @is_executable( $candidate ) ) { $bin = $candidate; break; }
        }
        if ( ! $bin ) {
            // Try PATH
            $found = trim( shell_exec( 'which wkhtmltopdf 2>/dev/null' ) );
            if ( $found && @is_executable( $found ) ) $bin = $found;
        }
        if ( ! $bin ) return null;

        $safe     = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $save_name ?: 'report' );
        $html_tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpi_src_' . $inspection_id . '_' . $safe . '.html';
        $pdf_tmp  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpi_out_' . $inspection_id . '_' . $safe . '.pdf';

        // Strip the print-bar and share-loading overlay — they're screen-only UI
        $clean_html = preg_replace( '/<div class="print-bar">.*?<\/div>/s', '', $html );
        $clean_html = preg_replace( '/<div id="wpi-share-loading".*?<\/div>\s*<\/div>/s', '', $clean_html );

        // Remove the in-document footer for wkhtmltopdf — native repeated footers are injected from settings.
        $clean_html = preg_replace( '/<div class="footer">.*?<\/div>\s*<\/div>/s', '</div>', $clean_html, 1 );

        // Inject wkhtmltopdf-specific CSS: force background colour on chip spans to print
        $wk_css = '<style>'
            . 'span[bgcolor]{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.answer-chip,.chip-yes,.chip-no,.chip-na{'
            . '-webkit-print-color-adjust:exact;print-color-adjust:exact;'
            . 'display:inline-block;}'
            . '</style>';
        $clean_html = str_replace( '</head>', $wk_css . '</head>', $clean_html );

        // Remove the padding-top:56px body rule added for the fixed print-bar
        $clean_html = str_replace( 'body{padding-top:56px;}', 'body{padding-top:0;}', $clean_html );

        // Remove .print-bar CSS block
        $clean_html = preg_replace( '/\/\* Print bar \*\/.*?\.wpi-standalone\{padding-top:0!important;\}/s', '', $clean_html );

        if ( file_put_contents( $html_tmp, $clean_html ) === false ) return null;

        $page_layout = self::get_page_layout( $cfg['page_margin'] ?? 'normal' );
        $args = array(
            '--page-size'          , 'A4',
            '--margin-top'         , (string) $page_layout['top'],
            '--margin-right'       , (string) $page_layout['right'],
            '--margin-bottom'      , (string) $page_layout['print_bottom'],
            '--margin-left'        , (string) $page_layout['left'],
            '--footer-spacing'     , '2',
            '--footer-font-size'   , '9',
            '--print-media-type'   ,
            '--enable-local-file-access',
            '--disable-smart-shrinking',
            '--zoom'               , '1',
            '--quiet'              ,
            '--background',
        );

        $footer_left   = self::resolve_wkhtml_footer_tokens( $cfg['footer_left']   ?? '', $inspection, $site, $date_str );
        $footer_center = self::resolve_wkhtml_footer_tokens( $cfg['footer_center'] ?? '', $inspection, $site, $date_str );
        $footer_right  = self::resolve_wkhtml_footer_tokens( $cfg['footer_right']  ?? '', $inspection, $site, $date_str );
        if ( empty($footer_left) && empty($footer_center) && empty($footer_right) && !empty($cfg['footer_text']) ) {
            $footer_left = self::resolve_wkhtml_footer_tokens( $cfg['footer_text'], $inspection, $site, $date_str );
        }
        if ( $footer_left !== '' )   { $args[] = '--footer-left';   $args[] = $footer_left; }
        if ( $footer_center !== '' ) { $args[] = '--footer-center'; $args[] = $footer_center; }
        if ( $footer_right !== '' )  { $args[] = '--footer-right';  $args[] = $footer_right; }
        $args[] = '--footer-line';

        $cmd_parts = array( escapeshellcmd( $bin ) );
        foreach ( $args as $a ) $cmd_parts[] = escapeshellarg( $a );
        $cmd_parts[] = escapeshellarg( 'file://' . $html_tmp );
        $cmd_parts[] = escapeshellarg( $pdf_tmp );

        $cmd    = implode( ' ', $cmd_parts ) . ' 2>&1';
        $output = array(); $rc = 0;
        exec( $cmd, $output, $rc );

        @unlink( $html_tmp );

        if ( $rc === 0 && file_exists( $pdf_tmp ) && filesize( $pdf_tmp ) > 0 ) {
            return $pdf_tmp;
        }

        // Log for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WPI wkhtmltopdf failed (rc=' . $rc . '): ' . implode( ' | ', $output ) );
        }
        @unlink( $pdf_tmp );
        return null;
    }

    /**
     * Try to generate PDF via headless Chrome/Chromium.
     * Returns temp file path or null.
     */
    private static function html_to_pdf_chrome( $html, $inspection_id, $save_name = 'report' ) {
        $bin = '';
        foreach ( array(
            '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium', '/usr/bin/chromium-browser',
            '/usr/local/bin/chromium', '/snap/bin/chromium',
        ) as $candidate ) {
            if ( @is_executable( $candidate ) ) { $bin = $candidate; break; }
        }
        if ( ! $bin ) {
            foreach ( array( 'google-chrome', 'chromium', 'chromium-browser' ) as $name ) {
                $found = trim( shell_exec( 'which ' . escapeshellarg($name) . ' 2>/dev/null' ) );
                if ( $found && @is_executable( $found ) ) { $bin = $found; break; }
            }
        }
        if ( ! $bin ) return null;

        $safe    = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $save_name ?: 'report' );
        $html_tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpi_ch_' . $inspection_id . '_' . $safe . '.html';
        $pdf_tmp  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpi_ch_' . $inspection_id . '_' . $safe . '.pdf';

        // Strip screen-only print-bar
        $clean = preg_replace( '/<div class="print-bar">.*?<\/div>/s', '', $html );
        $clean = str_replace( 'body{padding-top:56px;}', 'body{padding-top:0;}', $clean );

        if ( file_put_contents( $html_tmp, $clean ) === false ) return null;

        $cmd = escapeshellcmd( $bin )
            . ' --headless --disable-gpu --no-sandbox --disable-dev-shm-usage'
            . ' --print-to-pdf=' . escapeshellarg( $pdf_tmp )
            . ' --print-to-pdf-no-header'
            . ' ' . escapeshellarg( 'file://' . $html_tmp )
            . ' 2>/dev/null';

        exec( $cmd, $out, $rc );
        @unlink( $html_tmp );

        if ( $rc === 0 && file_exists( $pdf_tmp ) && filesize( $pdf_tmp ) > 0 ) {
            return $pdf_tmp;
        }
        @unlink( $pdf_tmp );
        return null;
    }

    /**
     * Resolve tokens in footer text: {date}, {template}, {site}, {inspector}, {score}, {page}, {pages}
     * Note: {page} and {pages} are replaced client-side via JS after print; here we output them as-is.
     */

    private static function format_display_datetime( $raw ) {
        if ( $raw === null || $raw === '' ) return '';
        $raw = trim( (string) $raw );
        if ( $raw === '' ) return '';
        try {
            $dt = new DateTime( $raw, wp_timezone() );
            return $dt->format('d M Y, g:i A');
        } catch ( Exception $e ) {
            if ( preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/', $raw, $m) ) {
                return date_i18n('d M Y, g:i A', mktime((int)$m[4], (int)$m[5], 0, (int)$m[2], (int)$m[3], (int)$m[1]));
            }
            return $raw;
        }
    }

    private static function format_token_value_by_type( $raw, $type ) {
        if ( $raw === null || $raw === '' ) return '';
        $type = (string) $type;
        if ( in_array( $type, array('datetime','date_time'), true ) ) {
            return self::format_display_datetime( $raw );
        }
        if ( $type === 'date' ) {
            try {
                $dt = new DateTime( (string) $raw, wp_timezone() );
                return $dt->format('d M Y');
            } catch ( Exception $e ) {
                return (string) $raw;
            }
        }
        if ( $type === 'time' ) {
            try {
                $dt = new DateTime( '1970-01-01 ' . trim((string) $raw), wp_timezone() );
                return $dt->format('g:i A');
            } catch ( Exception $e ) {
                return (string) $raw;
            }
        }
        return is_scalar( $raw ) ? (string) $raw : '';
    }



    private static function logic_requires_note( $item ) {
        $logic = isset($item->logic) && is_array($item->logic) ? $item->logic : array();
        $val   = strtolower(trim(isset($item->value) ? (string)$item->value : ''));
        foreach ( $logic as $rule ) {
            if ( !is_array($rule) ) continue;
            if ( !isset($rule['action']) || $rule['action'] !== 'require_note' ) continue;
            // No condition = always requires note
            if ( empty($rule['condition']) ) return true;
            $cond = $rule['condition'];
            $field = isset($cond['field']) ? $cond['field'] : 'value';
            $op    = isset($cond['op'])    ? $cond['op']    : 'equals';
            $cv    = strtolower(trim(isset($cond['value']) ? (string)$cond['value'] : ''));
            if ( $field !== 'value' ) continue;
            $match = false;
            if ( $op === 'equals'      ) $match = ($val === $cv);
            if ( $op === 'not_equals'  ) $match = ($val !== $cv);
            if ( $op === 'contains'    ) $match = (strpos($val, $cv) !== false);
            if ( $match ) return true;
        }
        return false;
    }

    private static function resolve_footer_tokens( $text, $inspection, $site, $date_str ) {
        if ( !$text ) return '';
        $replacements = array(
            '{date}'      => $date_str,
            '{template}'  => $inspection->template_title ?: $inspection->title,
            '{site}'      => $site ?: '',
            '{inspector}' => $inspection->inspector_name ?: '',
            '{score}'     => $inspection->score !== null ? round($inspection->score).'%' : '',
            '{time}'      => current_time('H:i'),
            '{page}'      => '<span class="wpi-page-num" style="font-variant-numeric:normal;">1</span>',
            '{pages}'     => '<span class="wpi-page-total" style="font-variant-numeric:normal;">1</span>',
        );
        return strtr($text, $replacements);
    }

    private static function resolve_children( $item, $child_rmap, $depth ) {
        $children = array();
        $logic = isset($item->logic) && is_array($item->logic) ? $item->logic : array();
        if ( empty($logic) ) return $children;
        $item_id  = isset($item->id)      ? $item->id      : '';
        $item_sec = isset($item->section) ? $item->section : 'General';
        // Use repeat-specific child_rmap if the item carries one (repeat section items)
        foreach ( $logic as $ridx => $rule ) {
            if ( !is_array($rule) ) continue;
            if ( !isset($rule['action']) || $rule['action'] !== 'add_question' ) continue;
            if ( empty($rule['child']['label']) ) continue;
            $ckey = 'child_' . $item_id . '_' . $ridx;
            $cr   = isset($child_rmap[$ckey]) ? $child_rmap[$ckey] : null;
            if ( !$cr ) continue;
            $cr_value  = isset($cr->value)  ? (string)$cr->value  : '';
            $cr_notes  = isset($cr->notes)  ? (string)$cr->notes  : '';
            $cr_photos = isset($cr->photos) && is_array($cr->photos) ? $cr->photos : array();
            if ( $cr_value === '' && count($cr_photos) === 0 && $cr_notes === '' ) continue;
            $child_logic = isset($rule['child']['logic']) && is_array($rule['child']['logic'])
                ? $rule['child']['logic'] : array();
            $child = (object)array(
                'id'     => $ckey,
                'label'  => $rule['child']['label'],
                'type'   => isset($rule['child']['type']) ? $rule['child']['type'] : 'yes_no',
                'value'  => $cr_value,
                'notes'  => $cr_notes,
                'photos' => $cr_photos,
                'logic'  => $child_logic,
                'section'=> $item_sec,
                'depth'  => $depth + 1,
            );
            $children[] = $child;
            foreach ( self::resolve_children($child, $child_rmap, $depth + 1) as $gc ) {
                $children[] = $gc;
            }
        }
        return $children;
    }


}
