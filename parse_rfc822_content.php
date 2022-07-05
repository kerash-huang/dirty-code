<?php
/**
it's a dirty code to parse rfc822 format content
the sample source data is from Gmail IMAP
put the plain text string and will return an array

main data index will named  content-body-{CONTENT-TYPE}
ex: contet-body-text-plain  or content-body-text-html
*/
function parse_rfc822_content($content) {
    $content_line = explode("\r\n", $content); // 切換行
    $content_data = array(); // 總內容

    $line_name = '';
    $is_in_content = false; // 是否已經進入內容
    $is_start_store_content = false; // 是否開始儲存內容
    $in_content_text  = ''; // 內容資料
    $is_new_dataset = false;
    $is_base64_type = false;
    foreach ($content_line as $_line) {
        if ($is_in_content or strpos($_line, '--') === 0) { // 實際的內容
            $is_new_dataset = false;
            if (strrpos($_line, '--') !== 0 and strrpos($_line, '--') !== false) { // 結尾
                $content_data[$line_name] = $in_content_text;
                $is_in_content = false;
                $is_start_store_content = false;
                $in_content_text = '';
                $line_name = '';
            } else {
                if (strpos($_line, '--') === 0) {
                    $is_in_content = true;
                    if ($line_name and $in_content_text) {
                        if ($is_base64_type) {
                            $in_content_text = trim($in_content_text, "\r\n\0");
                            $in_content_text = base64_decode($in_content_text);
                        }
                        $content_data[$line_name] = $in_content_text;
                    }
                    $is_start_store_content = false;
                    $is_base64_type = false;
                    $in_content_text = '';
                    $line_name = 'content-body';
                } else {
                    if (!$is_start_store_content) {
                        if (empty($_line)) {
                            $is_start_store_content = true;
                        } else {
                            if (strpos(strtolower($_line), 'content-type') !== false) {
                                $split_1 = explode(';', $_line, 2);
                                $split_2 = explode(': ', $split_1[0]);
                                $name_ext = str_replace('/', '-', $split_2[1]);
                                $line_name .= '-' . $name_ext;
                            } else if (strpos(strtolower($_line), 'content-transfer-encoding') !== false) {
                                if (strpos(strtolower($_line), 'base64') !== false) {
                                    $is_base64_type = true;
                                }
                            }
                        }
                    } else {
                        $in_content_text .= $_line . "\r\n";
                    }
                }
            }
        } else if (substr($_line, 0, 1) == ' ') { // 與此前同一份資料, line name 不換，直接串上內容
            if ($is_new_dataset) {
                $content_data[$line_name] .= ltrim("\r\n" . $_line, ' ');
            }
        } else {
            if (empty($_line)) { // 空的直接跳過
                continue;
            }
            $_dline_box = explode(':', $_line, 2);

            if (count($_dline_box) < 2) {
                $is_new_dataset = false;
                continue;
            }

            $line_name = strtolower($_dline_box[0]);
            $is_new_dataset = true;
            $content_data[$line_name] = $_dline_box[1];
        }
    }
    return $content_data;
}
