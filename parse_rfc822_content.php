<?php
/**
it's a dirty code to parse rfc822 format content
the sample source data is from Gmail IMAP
put the plain text string and will return an array

main data index will named  content-body-{CONTENT-TYPE}
ex: contet-body-text-plain  or content-body-text-html

add image index into [image-attachment] array
with filename as sub-index name
['image-attachment']['image.png']
*/
function parse_rfc822_content($content) {
    $content_line = explode("\r\n", $content); // 切換行
    $content_data = array(); // 總內容

    $line_name = '';
    $is_in_content = false; // 是否已經進入內容
    $is_start_store_content = false; // 是否開始儲存內容
    $in_content_text  = ''; // 內容資料
    $is_new_dataset = false; // 新一個資料
    $is_base64_type = false; // 是不是b64的內容
    $tmp_image_name = ''; // 暫存圖片名稱
    foreach ($content_line as $_line) { // 一行一行執行
        if ($is_in_content or strpos($_line, '--') === 0) { // 已經在存實際的內容(-- 開頭或者標定已在儲存)
            $is_new_dataset = false;
            if (strrpos($_line, '--') !== 0 and strrpos($_line, '--') !== false) { // end of content set

                if ($line_name and $in_content_text) {
                    if (!$tmp_image_name and $is_base64_type) {
                        $in_content_text = trim($in_content_text, "\r\n\0");
                        $in_content_text = base64_decode($in_content_text);
                    }
                    if ($tmp_image_name) {
                        $content_data[$line_name][$tmp_image_name] = $in_content_text;
                    } else {
                        $content_data[$line_name] = $in_content_text;
                    }
                }

                $is_in_content = false;
                $is_start_store_content = false;
                $is_base64_type = false;
                $in_content_text = '';
                $line_name = '';
                $tmp_image_name = '';
            } else {
                if (strpos($_line, '--') === 0) {
                    $is_in_content = true;
                    if ($line_name and $in_content_text) {
                        if (!$tmp_image_name and $is_base64_type) {
                            $in_content_text = trim($in_content_text, "\r\n\0");
                            $in_content_text = base64_decode($in_content_text);
                        }
                        if ($tmp_image_name) {
                            $content_data[$line_name][$tmp_image_name] = $in_content_text;
                        } else {
                            $content_data[$line_name] = $in_content_text;
                        }
                    }
                    $tmp_image_name = '';
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

                                if (strpos($name_ext, 'image') !== false) {
                                    $line_name = 'image-attachment';
                                    // 圖
                                    $split_2 = explode("=", $split_1[1]);
                                    $img_file_name = $split_2[1];
                                    $img_file_name = str_replace('"', '', $img_file_name);
                                    $tmp_image_name = $img_file_name;
                                }
                            } else if (strpos(strtolower($_line), 'content-transfer-encoding') !== false) {
                                if (!$tmp_image_name and strpos(strtolower($_line), 'base64') !== false) {
                                    $is_base64_type = true;
                                }
                            }
                        }
                    } else {
                        $in_content_text .= $_line;
                        if (!$tmp_image_name) {
                            $in_content_text .= "\r\n";
                        }
                    }
                }
            }
        } else if (substr($_line, 0, 1) == ' ') { // for header 的，與此前同一份資料, line name 不換，直接串上內容
            if ($is_new_dataset) {
                if ($tmp_image_name) {
                    $content_data[$line_name][$tmp_image_name] .= ltrim("\r\n" . $_line, ' ');
                } else {
                    $content_data[$line_name] .= ltrim("\r\n" . $_line, ' ');
                }
            }
        } else { // for header 撈 source mail , topic ... etc 資料用
            if (empty($_line)) { // 空的直接跳過
                continue;
            }
            $_dline_box = explode(':', $_line, 2);

            if (count($_dline_box) < 2) {
                $is_new_dataset = false;
                continue;
            }

            $line_name = strtolower($_dline_box[0]);
            $is_new_dataset = true;  // 新一行資料
            $content_data[$line_name] = $_dline_box[1]; // 這時候通常不會有 image 所以不用管
        }
    }
    return $content_data;
}
