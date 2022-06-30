<?php
/**
it's a dirty code to parse rfc822 format content
the sample source data is from Gmail IMAP
put the plain text string and will return an array

main data index will named  content-body-{CONTENT-TYPE}
ex: contet-body-text-plain  or content-body-text-html
*/
function parse_rfc822_content($content) {
    $content_line = explode("\r\n", $content);
    $content_data = array(); 

    $line_name = '';
    $is_in_content = false; 
    $is_start_store_content = false; 
    $in_content_text  = ''; 
    $is_new_dataset = false;
    foreach ($content_line as $_line) {
        if ($is_in_content or strpos($_line, '--') === 0) {
            $is_new_dataset = false;
            if (strrpos($_line, '--') !== 0 and strrpos($_line, '--') !== false) { 
                $content_data[$line_name] = $in_content_text;
                $is_in_content = false;
                $is_start_store_content = false;
                $in_content_text = '';
                $line_name = '';
            } else {
                if (strpos($_line, '--') === 0) {
                    $is_in_content = true;
                    if ($line_name and $in_content_text) {
                        $content_data[$line_name] = $in_content_text;
                        $is_start_store_content = false;
                    }
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
                            }
                        }
                    } else {
                        $in_content_text .= $_line . "\r\n";
                    }
                }
            }
        } else if (substr($_line, 0, 1) == ' ') { 
            if ($is_new_dataset) {
                $content_data[$line_name] .= ltrim(' ', $_line . "\r\n");
            }
        } else {
            if (empty($_line)) { 
                continue;
            }
            $_dline_box = explode(':', $_line, 2);

            if (count($_dline_box) < 2) {
                $is_new_dataset = false;
                continue;
            }

            $line_name = $_dline_box[0];
            $is_new_dataset = true;
            $content_data[$line_name] = $_dline_box[1];
        }
    }
    return $content_data;
}
