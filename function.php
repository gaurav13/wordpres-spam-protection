<?php
/* enqueue scripts and style from parent theme */
function twentytwentyone_styles()
{
    wp_enqueue_style("child-style", get_stylesheet_uri(), ["twenty-twenty-one-style"], wp_get_theme()->get("Version"));
}
add_action("wp_enqueue_scripts", "twentytwentyone_styles");
if (!function_exists("energo_theme_setup")) {
    function energo_theme_setup()
    {
        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        register_nav_menu("top", __("Top Menu", "twentytwentyone"));
    }
    add_action("after_setup_theme", "energo_theme_setup");
}
add_filter('wpcf7_form_hidden_fields', 'inject_custom_hidden_fields');
function inject_custom_hidden_fields($hidden_fields) {
    $hidden_fields['form_load_time'] = time(); // inject timestamp
    $hidden_fields['custom_nonce'] = wp_create_nonce('custom_popup_form_nonce');
    return $hidden_fields;
}
/*Allow Span tags in editor*/
function fb_change_mce_options($initArray)
{
    $ext = 'pre[id|name|class|style], span[id|name|class|style], p[id|name|class|style],i[id|name|class|style],em[id|name|class|style],iframe[align|longdesc| name|width|height|frameborder|scrolling|marginheight| marginwidth|src]';
    if (isset($initArray['extended_valid_elements'])) {
        $initArray['extended_valid_elements'] .= ',' . $ext;
    } else {
        $initArray['extended_valid_elements'] = $ext;
    }
    return $initArray;
}
add_filter('tiny_mce_before_init', 'fb_change_mce_options');

function themprefix_faq_script() {
    wp_enqueue_script('jquery');

    // Slick assets
    wp_enqueue_style("slick", "//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css", [], "1.8.1");
    wp_enqueue_style("slick-theme", "//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css", [], "1.8.1");
    wp_enqueue_script("slick", "//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js", ["jquery"], "1.8.1", true);

    // FontAwesome and custom styles
    wp_enqueue_style("fontawesome", "//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", [], "4.3.0");
    wp_enqueue_style("faq-css", get_stylesheet_directory_uri() . "/css/custom.css", [], "1.0");
    wp_enqueue_style('devinline-css', get_stylesheet_directory_uri() . '/css/devinline.css');
    wp_enqueue_style("responsive-css", get_stylesheet_directory_uri() . '/css/responsive.css');
    if (is_page_template('svjpage.php') || is_page_template('B2Cpage.php')) {
        wp_enqueue_style('svj-css', get_stylesheet_directory_uri() . '/css/svjpage.css');
    }
    wp_enqueue_style("main-style", get_stylesheet_uri(), [], "1.2");

    // Custom JS (depends on slick)
    wp_enqueue_script("custom-js", get_stylesheet_directory_uri() . "/js/custom.js", ["jquery", "slick"], "1.1", true);

    // Localized script
    wp_localize_script("custom-js", "my_ajax_object", array(
        "ajaxurl" => admin_url("admin-ajax.php"),
        "security" => wp_create_nonce("file_upload"),
        "recaptcha_site_key" => RECAPTCHA_SITE_KEY, 
    ));
}
add_action("wp_enqueue_scripts", "themprefix_faq_script");



add_filter("wpcf7_support_html5_fallback", "__return_true");
if (function_exists("acf_add_options_page")) {
    acf_add_options_page(["page_title" => "Theme General Settings", "menu_title" => "Theme Settings", "menu_slug" => "theme-general-settings", "capability" => "edit_posts", "redirect" => false,]);
    $parent = acf_add_options_sub_page(["page_title" => "Theme Header Settings", "menu_title" => "Header", "parent_slug" => "theme-general-settings",]);
    acf_add_options_sub_page(["page_title" => "Theme Footer Settings", "menu_title" => "Footer", "parent_slug" => "theme-general-settings",]);
    acf_add_options_sub_page(["page_title" => "Client Slider", "menu_title" => "Client Slider", "parent_slug" => "theme-general-settings",]);
    acf_add_options_sub_page(["page_title" => "Kariéra Page Settings", "menu_title" => "Kariéra Page Settings", "parent_slug" => "theme-general-settings",]);
    acf_add_options_sub_page(["page_title" => "Client Testimonials", "menu_title" => "Testimonial Page Settings", "parent_slug" => "theme-general-settings",]);
    acf_add_options_sub_page(["page_title" => "B2B Testimonials", "menu_title" => "B2B Testimonial Page Settings", "parent_slug" => "theme-general-settings",]);
}


add_action('init', 'process_raj_crm');
function process_raj_crm()
{
    global $wpdb;
    $phonenumber_user = '';
    $email_user = '';
    $m = 0;
    $nowdate = date('Y-m-d H:i:s');
    $five_min_earlier = date('Y-m-d H:i:s', strtotime("-5 minute", strtotime($nowdate)));
    $one_hour_earlier = date('Y-m-d H:i:s', strtotime("-360 minute", strtotime($nowdate)));

    $formentries = $wpdb->get_results("SELECT * FROM `wpih_cf7_data_entry` where wpih_cf7_data_entry.name='submit_user_id' and value=0 and data_id in (SELECT data_id FROM wpih_cf7_data_entry WHERE name ='submit_time' and value < '" . $five_min_earlier . "' and value >= '" . $one_hour_earlier . "') order by id desc", OBJECT);
    if (!empty($formentries)) {
        foreach ($formentries as $formentry) {
            $form_number = $formentry->data_id;
            $phoneentries = $wpdb->get_results("SELECT * FROM `wpih_cf7_data_entry` where wpih_cf7_data_entry.data_id='" . $form_number . "' ", OBJECT);
            foreach ($phoneentries as $phoneentry) {

                if ($phoneentry->name == 'telefon') {
                    $phonenumber_user = $phoneentry->value;
                }
                if ($phoneentry->name == 'email') {
                    $email_user = $phoneentry->value;
                }
            }
            $m++;
            
            $wpdb->query($wpdb->prepare("UPDATE `wpih_cf7_data_entry` SET value='99999' WHERE data_id=$form_number and name ='submit_user_id'"));
            $exDetails = $wpdb->get_results("SELECT max(id) as maxId FROM contactformincrement", OBJECT);
            $exDetail = $exDetails[0];
            $maxId = $exDetail->maxId;
            $newid = $maxId + 1;
            $newmaxId = number_format($maxId, 0, ",", " ");
            $inserted = $wpdb->insert("contactformincrement", ["id" => $newid,], ["%d"]);
            $topic = "Callback (nedokončená) č. (" . $newmaxId . ")";
            $body = "<p>Právě dorazila nová objednávka/zpráva od zákazníka:</p> ";
            $body .= "<p>Telefon: " . $phonenumber_user . "</p>";
            if ($email_user != '') {
                $body .= "<br>Email: " . $email_user;
            }
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: EnergoSolar <sales@energosolar.cz>');
            $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();
            $parsed = wp_parse_url($referer);
            $domain = isset($parsed['host']) ? $parsed['host'] : parse_url(home_url(), PHP_URL_HOST);
            $domain = preg_replace('/^www\./', '', $domain); // remove www if exists

            $admin_email = 'sales@' . $domain;
            $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: EnergoSolar.cz <' . $admin_email . '>',
            'Reply-To: ' . $admin_email,
        );
            if ($phonenumber_user != '') {
                wp_mail($admin_email, $topic, $body, $headers);
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://app.raynet.cz/api/v2/lead",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "PUT"
                    ,
                    CURLOPT_POSTFIELDS => '{
                        "topic": "' . $topic . '",
                        "firstName": "' . $firstname . '",
                        "lastName": "' . $lastname . '",
                        "companyName": "' . $company_name . '",
                        "owner":27,
                        "priority": "DEFAULT",
                        "leadPhase": 117,
                        "contactSource":"' . $contactSource . '",
                        "notice": "' . $msg_full . '",
                        "contactInfo": {
                            "email": "' . $email_user . '",
                            "email2": null,
                            "tel1": "' . $phonenumber_user . '",
                            "tel1Type": null,
                            "tel2": null,
                            "tel2Type": null,
                            "fax": null,
                            "www": null,
                            "otherContact": null
                        },
                        "address": {
                        "city": "' . $okres1 . '",
                        "countryName": "Česká republika",
                        "countryCode": "CZ",
                        "province": "' . $Kraj1 . '"
                        }

                    }',
                    CURLOPT_HTTPHEADER => ["X-Instance-Name: energosolarcrm", "Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=", "Content-Type: text/plain",],
                ]);
                $response = curl_exec($curl);
                curl_close($curl);
            }
        }
    }
}
add_action("wpcf7_before_send_mail", "my_change_subject_mail");
function my_change_subject_mail($WPCF7_ContactForm)
{
    $current_form_id = $WPCF7_ContactForm->id();
        if ($current_form_id == '6656') {
                global $wpdb;
                $submission = WPCF7_Submission::get_instance();

                // Bot Protection Start 
                if (!$submission) return;

                $wpcf7 = WPCF7_ContactForm::get_current();
                if (!$wpcf7) return;
                
                $posted_data = $submission->get_posted_data();
                error_log('[CF7 DEBUG] Raw $posted_data: ' . print_r( $posted_data, true ) );

                if (empty($posted_data)) return;

                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $honeypot = trim($posted_data['honeypot-240'] ?? '');
                $form_load_time = (int)($posted_data['form_load_time'] ?? 0);
                $submission_duration = time() - $form_load_time;
                $nonce = $posted_data['custom_nonce'] ?? '';
                $email = sanitize_email($posted_data['email'] ?? '');
                $firstname = $submission->get_posted_data("jmeno");
                $lastname = $submission->get_posted_data("prijmenu");
                $phone = $posted_data['telefon'] ?? '';
                $zaka = $posted_data['zaka'] ?? '';
                $zakapro = is_array($zaka) ? strtolower(trim($zaka[0])) : strtolower(trim($zaka));
                $bot_trap_values = ['ostatní/jinée', 'dummy', 'hiddenbot', 'botoption'];
                $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();

                // === quick helper log function ===
                $cf7_logger = function($status, $message, $extra = []) use ($posted_data, $current_form_id, $email, $phone, $referer) {
                    log_form_submission([
                        'form_type'      => 'cf7',
                        'form_id'        => $current_form_id,
                        'submitted_data' => $posted_data,
                        'status'         => $status,
                        'error_message'  => $message,
                        'page_url'       => $referer,
                    ]);
                    error_log("[CF7 {$status}] {$message} | Email: {$email}, Phone: {$phone}");
                };

                // 1. Honeypot check
           /*      if (!empty($honeypot)) {
                    error_log("[SPAM][honeypot] $ip, $email");
                    $submission->set_response("Spam blokován (honeypot).");
                    $wpcf7->skip_mail = true;
                    // $cf7_logger('error', 'Honeypot triggered');
                    return;
                }
 */  
 /* if (!empty($honeypot)) {
    // check that honeypot really looks like a bot, not a browser artefact
    if (strlen($honeypot) > 2) {
        error_log("[SPAM][honeypot triggered] $ip, $email");
        $submission->set_response("Formulář blokován jako spam.");
        $wpcf7->skip_mail = true;
        return;
    }
} */

         /*       if (preg_match('/(test|fake|mailinator|example)\./i', $email)) {
    error_log("[SPAM][bad email domain] $email");
    $wpcf7->skip_mail = true;
    return;
} */

                // 2. Fast submit check (<8s)
                if ($submission_duration > 0 && $submission_duration < 12) {
                    error_log("[SPAM][fast] $submission_duration sec - $ip, $email");
                    $submission->set_response("Spam blokován (rychlé odeslání).");
                    $wpcf7->skip_mail = true;
                    // $cf7_logger('error', "Too fast submit ({$submission_duration}s)");
                    return;
                }

                // 3. Dummy select trap
                if (in_array($zakapro, $bot_trap_values, true)) {
                    error_log("[SPAM][dummy zakapro: $zakapro] $ip, $email");
                    $submission->set_response("Spam blokován (zakázka dummy).");
                    $wpcf7->skip_mail = true;
                    // $cf7_logger('error', "Dummy zakapro triggered: {$zakapro}");
                    return;
                }

                // 4. Nonce check
                if (!wp_verify_nonce($nonce, 'custom_popup_form_nonce')) {
                    error_log("[SPAM][invalid nonce] $ip, $email - Nonce: $nonce");
                    $submission->set_response("Neplatný bezpečnostní token.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "Nonce expired/invalid: {$nonce}");
                    return;
                }

                if (!is_email($email)) {
                    $submission->set_response("Neplatný e-mail.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "Invalid email format: {$email}");
                    return;
                }
								
						/* 		// --- Extra Spam Email Checks ---
				$spam_domains = [
					'mailinator.com', 'tempmail', '10minutemail', 'guerrillamail', 'sharklasers',
					'example.com', 'test.com', 'yopmail', 'dispostable', 'trashmail', 'fakeinbox',
					'temporary-mail', 'inboxkitten', 'spamgourmet', 'getnada', 'centermail'
				];
				$spam_patterns = [
					'/^test\d*@/i',          // test123@
					'/^fake\d*@/i',          // fake123@
					'/^mail\d*@/i',          // mail111@
					'/^asd.*@/i',            // asd@
					'/^[0-9]{3,}@/i',        // numeric only
					'/^.{0,2}@/i',           // too short local part
					'/(abc|qwe|zzz|xxx)/i'   // random junk
				];

								// --- Domain blacklist ---
				foreach ($spam_domains as $bad) {
					if (stripos($email, $bad) !== false) {
						$submission->set_response("E-mailová adresa není povolena.");
						$submission->set_status('validation_failed'); // ❌ Stop form as invalid
						$submission->add_invalid_field('email', 'E-mailová adresa není povolena.'); // mark field invalid
						$wpcf7->skip_mail = true;
						$cf7_logger('error', "[SPAM][bad email domain] $email");
						return;
					}
				}

				// --- Pattern blacklist ---
				foreach ($spam_patterns as $pattern) {
					if (preg_match($pattern, $email)) {
						$submission->set_response("E-mailová adresa není povolena.");
						$submission->set_status('validation_failed'); // ❌ Stop form
						$submission->add_invalid_field('email', 'E-mailová adresa není povolena.');
						$wpcf7->skip_mail = true;
						$cf7_logger('error', "[SPAM][pattern email] $email");
						return;
					}
				}
 */

				// MX record validation (real domain check)
				$domain = substr(strrchr($email, "@"), 1);
				if (!checkdnsrr($domain, "MX")) {
					$submission->set_response("E-mailová doména neexistuje.");
					$wpcf7->skip_mail = true;
					$cf7_logger('error', "[SPAM][no MX record] $email");
					return;
				}

				
                // Names containing numbers
                if (preg_match('/[0-9]/', $firstname) || preg_match('/[0-9]/', $lastname)) {
                    $submission->set_response("Zadání obsahuje neplatné znaky.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "[SPAM][numeric name] $ip, $email, $firstname $lastname");
                    return;
                }
                
                // Names too short
                if (strlen($firstname) < 2 || strlen($lastname) < 2) {
                    $submission->set_response("Jméno nebo příjmení je příliš krátké.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "[SPAM][short name] $ip, $email, $firstname $lastname");
                    return;
                }
                
                // Names with all caps
                if (preg_match('/[A-Z]{3,}/', $firstname) && preg_match('/[A-Z]{3,}/', $lastname)) {
                    $submission->set_response("Jméno nebo příjmení nemůže být psáno velkými písmeny.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "[SPAM][all caps name] $ip, $email, $firstname $lastname");
                    return;
                }
                
                // Names with long gibberish
                if (preg_match('/[a-zA-Z]{6,}/', $firstname) && !preg_match('/^[A-Z][a-z]+$/', $firstname)) {
                    $submission->set_response("Zadání obsahuje neplatné znaky.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "[gibberish name] $firstname $lastname");
                    return;
                }
                if (preg_match('/[a-zA-Z]{6,}/', $lastname) && !preg_match('/^[A-Z][a-z]+$/', $lastname)) {
                    $submission->set_response("Zadání obsahuje neplatné znaky.");
                    $wpcf7->skip_mail = true;
                    $cf7_logger('error', "[gibberish name] $firstname $lastname");
                    return;
                }
                //NEW CHECKS ENDD
            // Bot Protection Ended

            $exDetails = $wpdb->get_results("SELECT max(id) as maxId FROM contactformincrement", OBJECT);
            $exDetail = $exDetails[0];
            $maxId = $exDetail->maxId;
            $newid = $maxId + 1;
            $submission->get_posted_data('message');
            if(strlen($submission->get_posted_data("telefon")) > 20){
                // $abort = true;
            $submission->set_response("Je tam chyba. Kontaktujte nás na <a href='mailto:info@energosolar.cz'>info@energosolar.cz</a>info@energosolar.cz");
            $cf7_logger('error', "Phone too long: {$phone}");
            return;
        }
        $newmaxId = number_format($maxId, 0, ",", " ");

        /* ---------- E‑mail preparation ---------- */

            $mail  = $wpcf7->prop( 'mail' );
            $mail2 = $wpcf7->prop( 'mail_2' );

        $inserted = $wpdb->insert("contactformincrement", ["id" => $newid,], ["%d"]);
        
            if ($inserted === false) {
                $cf7_logger('error', 'Failed to insert into contactformincrement: ' . $wpdb->last_error);
                $wpcf7->skip_mail = true;
                return;
            }

        $clockorder = $submission->get_posted_data("clockorder");
            if (!empty($clockorder)) {
                global $wpdb;

                // Try insert
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT IGNORE INTO ehubformincrement (ehubNumber) VALUES (%s)",
                        $clockorder
                    )
                );

                if ($result === 0) {
                    $cf7_logger('error', "Ehub insert failed or duplicate: {$clockorder}");
                    // Insert failed (duplicate) → generate a new one
                    if (function_exists('get_random_ehub_number')) {
                        $clockorder = get_random_ehub_number();

                        $wpdb->insert(
                            'ehubformincrement',
                            ['ehubNumber' => $clockorder],
                            ['%s']
                        );
                    }
                }
            }
    
        $lead_inserted = $wpdb->insert('wpih_leads', array(
                'form_id' => $maxId,
                'order_type' => $submission->get_posted_data("zaka"),
                'name' => $submission->get_posted_data("jmeno"),
                'surname' => $submission->get_posted_data("prijmenu"),
                'firmy-sv' => $submission->get_posted_data("firmy-svj"),
                'phone' => $submission->get_posted_data("telefon"),
                'email' => $submission->get_posted_data("email"),
                'region' => $submission->get_posted_data("Kraj"),
                'district' => $submission->get_posted_data("Okres"),
                'form_type' => 'popup',
                'notes' => $submission->get_posted_data("message"),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'ehubNumber' => $clockorder,
                'Current_Page' => $referer
            ));

            if ($lead_inserted === false) {
                $cf7_logger('error', 'Failed to insert lead: ' . $wpdb->last_error);
                $wpcf7->skip_mail = true;
                return;
            }
            if ($submission) {
                $posted_data = $submission->get_posted_data();
                    // nothing's here... do nothing...
                if (empty($posted_data)) {
                    return;
                }
                $subject = $posted_data["your-message"];
                $today_date = dateToCzech("now", "j. F  Y");
                $zaka = $submission->get_posted_data("zaka");
                $zakapro = $zaka[0];
                if ($zakapro == 'VIP elektrárna') {
                    $sub_zakapro = 'VIP elektrárna';
                }
                if ($zakapro == 'Fotovoltaika s umělou inteligencí') {
                    $sub_zakapro = 'FVE s AI';
                }
                if ($zakapro == 'Fotovoltaika pro spotřebitele/domácnost') {
                    $sub_zakapro = 'FVE domácnosti';
                }
                if ($zakapro == 'Tepelná čerpadla') {
                    $sub_zakapro = 'Tepelná čerpadla';
                }
                if ($zakapro == 'Fotovoltaika s čerpadlem/elektronabíječkou') {
                    $sub_zakapro = 'FVE s tepelným čerpadlem/elektronabíječkou';
                }
                if ($zakapro == 'Firmy a průmysl') {
                    $sub_zakapro = 'Firmy';
                }
                if ($zakapro == 'SVJ a bytové domy') {
                    $sub_zakapro = 'SVJ';
                }
                if ($zakapro == 'Komerční bateriová úložiště') {
                    $sub_zakapro = 'Komerční bateriová úložiště';
                }
                if ($zakapro == 'Ostatní/jiné') {
                    $sub_zakapro = 'Ostatní';
                }

                $zaka = $submission->get_posted_data("zaka");
                $zakapro = $zaka[0];
                $company_name = $firstname . " " . $lastname;
                $phone = $submission->get_posted_data("telefon");
                $email = $submission->get_posted_data("email");
                $okres = $submission->get_posted_data("Okres"); //cf_965
                $okres1 = $okres[0];
                $Kraj = $submission->get_posted_data("Kraj");
                $Kraj1 = $Kraj[0];
                $gclid = $submission->get_posted_data("gclid");
                $fbclid = $submission->get_posted_data("fbclid");
                $sznclid = $submission->get_posted_data("sznclid"); //utm_source
                $utm_source = $submission->get_posted_data("utm_source");
                $utm_campaign = $submission->get_posted_data("utm_campaign");
                $formOrigin = $submission->get_posted_data("poup_form_origin");
                //$sznclid = $submission->get_posted_data('sznclid');
                $contactSource = "79";
                if ($gclid) {
                    $contactSource = "261";
                }
                if ($fbclid) {
                    $contactSource = "237";
                }
                if ($sznclid) {
                    $contactSource = "260";
                }
                if ($utm_source == "sklik") {
                    $contactSource = "264";
                }
                // $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();
                $parsed = wp_parse_url($referer);
                $domain = isset($parsed['host']) ? $parsed['host'] : parse_url(home_url(), PHP_URL_HOST);
                $domain = preg_replace('/^www\./', '', $domain); // remove www if exists

                $admin_email = 'sales@' . $domain;
                $from_name = 'Energosolar.cz';
                $reply_to = $admin_email;

                // === Update mail 1 (admin)
                $mail['recipient'] = $admin_email;
                $mail['additional_headers'] = "Reply-To: {$reply_to}\r\n";
                $mail['sender'] = "{$from_name} <{$admin_email}>";
                $mail['from'] = "{$from_name} <{$admin_email}>";

                $topic = "Nová poptávka: {$sub_zakapro} - {$firstname} {$lastname} ({$newmaxId}) na Energosolar.cz";
                if (!empty($formOrigin)) {
                    $topic .= " - {$formOrigin}";
                }
                $topic .= " ({$today_date})";
                $mail_subject  = $topic;
                $mail["subject"] = $mail_subject;
                   						$admin_email = 'sales@' . $domain;
$headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: Energosolar.cz <' . $admin_email . '>',
    'Reply-To: ' . $admin_email,
);
                $mail2['recipient'] = $email;
                $mail2['additional_headers'] = "Reply-To: {$reply_to}\r\n";
                $mail2['sender'] = "{$from_name} <{$admin_email}>";
                $mail2['from'] = "{$from_name} <{$admin_email}>";

                $mail2["subject"] = "Kopie údajů z vaší poptávky (" . $newmaxId . ") na Energosolar.cz (" . $today_date . ")";
                $wpcf7->set_properties( [ 'mail' => $mail, 'mail_2' => $mail2 ] );
                error_log('[CF7 DEBUG] Mail object after modification: ' . print_r( $mail, true ) );

                $financovani = $submission->get_posted_data("financovani");
                $emailoptout = $submission->get_posted_data("emailoptout");
                $message = str_replace(['"',"'"], "", $submission->get_posted_data('message'));
                $message_hidden = $submission->get_posted_data('message_hidden');
                $message = trim($message);
                // If messsage field value is empty then set abort to true which will not sent email
                if (empty($message_hidden)) {
                    if (strpos($clockorder, "yandex") == '') {
                        if ($financovani == 1) {
                            $financovani = "Ano";
                        } else {
                            $financovani = "Ne";
                        }
                        if ($emailoptout == 1) {
                            $emailoptout = "Ano";
                        } else {
                            $emailoptout = "Ne";
                        }
                        $msg = $submission->get_posted_data("message"); //[message][financovani][emailoptout]
                        $msg = sanitize_text_field($msg);
                        $msg_full = $msg . "<br> Financovani: " . $financovani . "<br> Emailoptout: " . $emailoptout . "<br> Zakázka pro: " . $zakapro;
                        $msg_full = "<br> From: " . $firstname . "<br><br> Právě dorazila nová objednávka/zpráva od zákazníka: " . $zakapro . "<br><br> Jméno: " . $company_name . "<br> E-mail: " . $email . "<br> Telefon: " . $phone . "<br> Kraj: " . $Kraj1 . "<br> Okres: " . $okres1 . "<br> Mám zájem o financování: " . $financovani . "<br> Souhlasím s využitím svých údajů pro marketingové účely: " . $emailoptout . "<br><br> Zpráva: " . $msg . "<br> Id: " . $clockorder . "<br><br> Tento formulář byl odeslán z Energosolar.cz: " . $_SERVER['HTTP_REFERER'];
						
                        ###########  LMS ENTRY ##########
                        $curl = curl_init();
                        $ch = curl_init();
                        // Set the URL
                        curl_setopt($ch, CURLOPT_URL, "https://app.raynet.cz/api/v2/lead");
                        // Indicate it's a POST request
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                        // Attach JSON data
                        $data = json_encode([
                        'topic' => $topic,
                        'firstName' => $firstname,
                        'lastName' => $lastname,
                        'companyName' => $company_name,
                        'owner' => 27,
                        'priority' => "DEFAULT",
                        'leadPhase' => 117,
                        'contactSource' => $contactSource,
                        'notice' => $msg_full,
                        'contactInfo' => [
                        'email' => $email,
                        'email2' => null,
                        'tel1' => $phone,
                        'tel1Type' => null,
                        'tel2' => null,
                        'tel2Type' => null,
                        'fax' => null,
                        'www' => null,
                        'otherContact' => null
                    ],
                    'address' => [
                        'city' => $okres1,
                        'countryName' => "Česká republika",
                        'countryCode' => "CZ",
                        'province' => $Kraj1
                    ]
                ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        // Set headers
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "X-Instance-Name: energosolarcrm",
                        "Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=",
                        'Content-Type: application/json',
                    ]);
                        // Set options
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        // Execute the request
                        $response = curl_exec($ch);
                        if ($response === false) {
                            $cf7_logger('error', "Raynet API cURL failed: " . curl_error($ch));
                        }
                        #########   END #############
                        $data = json_decode($response);
                        if($data->status == 500){
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://app.raynet.cz/api/v2/lead",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "PUT"
                                /*jana owner*/ ,
                                CURLOPT_POSTFIELDS => '{
                                    "topic": "' . $topic . '",
                                    "firstName": "' . $firstname . '",
                                    "lastName": "' . $lastname . '",
                                    "companyName": "' . $company_name . '",
                                    "owner":27,
                                    "priority": "DEFAULT",
                                    "leadPhase": 117,
                                    "contactSource":"' . $contactSource . '",
                                    "notice": "",
                                    "contactInfo": {
                                        "email": "' . $email . '",
                                        "email2": null,
                                        "tel1": "' . $phone . '",
                                        "tel1Type": null,
                                        "tel2": null,
                                        "tel2Type": null,
                                        "fax": null,
                                        "www": null,
                                        "otherContact": null
                                        },
                                        "address": {
                                            "city": "' . $okres1 . '",
                                            "countryName": "Česká republika",
                                            "countryCode": "CZ",
                                            "province": "' . $Kraj1 . '"
                                        }
                                    }',
                                    CURLOPT_HTTPHEADER => ["X-Instance-Name: energosolarcrm", "Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=", "Content-Type: text/plain",],
                                ]);
                            $response = curl_exec($curl);
                        }
                        if ($emailoptout == "Ano") {
                            curl_setopt_array(
                                $curl,
                                array(
                                    CURLOPT_URL => 'https://app.raynet.cz/api/v2/gdpr',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'PUT',
                                    CURLOPT_POSTFIELDS => '{
                                        "lead": ' . $data->data->id . ',
                                        "gdprTemplate": 2,
                                        "validFrom": "' . date("Y-m-d") . '",
                                        "validTill": "' . date('Y-m-d', strtotime('+2 years', strtotime(date("Y-m-d")))) . '"
                                    }',
                                    CURLOPT_HTTPHEADER => array(
                                        'Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=',
                                        'X-Instance-Name: energosolarcrm',
                                        'Content-Type: text/plain'
                                    ),
                                )
                            );
                            $response = curl_exec($curl);
                        }
                        curl_close($curl);
                    }
                }
            } // submission ends here
            $cf7_logger('success', "Form submitted successfully. Lead inserted with ID {$newid}");
        }
	###  END  #########
}
/*function ends here*/

function wpcf7_before_send_mail_function($contact_form, &$abort, $submission)
{
    $form_id = $contact_form->id();
    // Compare the form Id so other form will work as it is.
    //if( $form_id == 70 ) {
    // Get field data. For example message field
    $message = $submission->get_posted_data('message_hidden');
    $message = trim($message);
    // If messsage field value is empty then set abort to true which will not sent email
    if (!empty($message)) {
        $submission->set_response('Při zpracování formuláře došlo k neznámé chybě (možné nejčastější příčiny: text je příliš dlouhý, obsahuje nedovolené znaky atd.). Po opravě zkuste poté zprávu odeslat ještě jednou. Pokud se vám stále zobrazuje tato chybová hláška, napište nám prosím na email info@energosolar.cz. Děkujeme.');
        $abort = true;
    }
    //}
}
add_action('wpcf7_before_send_mail', 'wpcf7_before_send_mail_function', 10, 3);

add_filter("wpcf7_mail_tag_replaced_acceptance", function ($replaced, $submitted, $html, $mail_tag) {
    return !empty($submitted) ? 'Ano' : 'Ne';
}, 10, 4);

// add_filter("wpcf7_mail_tag_replaced_acceptance", "wpcf7_acceptance_mail_tag2", 10, 4);
function wpcf7_acceptance_mail_tag2($replaced, $submitted, $html, $mail_tag)
{
    $form_tag = $mail_tag->corresponding_form_tag();
    if (!$form_tag) {
        return $replaced;
    }
    if (!empty($submitted)) {
        $replaced = __("Ano", "contact-form-7");
    } else {
        $replaced = __("Ne", "contact-form-7");
    }
    $content = empty($form_tag->content) ? (string) reset($form_tag->values) : $form_tag->content;
    if (!$html) {
        $content = wp_strip_all_tags($content);
    }
    $content = trim($content);
    if ($content) {
        $replaced = sprintf(
            /* translators: 1: 'Consented' or 'Not consented', 2: conditions */
            _x('%1$s: %2$s', "mail output for acceptance checkboxes", "contact-form-7"),
            $content,
            $replaced
        );
    }
    return $replaced;
}
function dateToCzech($date, $format)
{
    $english_days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",];
    $czech_days = ["pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle",];
    $english_months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December",];
    $czech_months = ["leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec",];
    return str_replace($english_months, $czech_months, str_replace($english_days, $czech_days, date($format, strtotime($date))));
}
// REGISTER CUSTOM POST TYPES
// You can register more, just duplicate the register_post_type code inside of the function and change the values. You are set!
if (!function_exists("create_post_type")):
    function create_post_type()
    {
        // You'll want to replace the values below with your own.
        register_post_type(
            "faq", // change the name
            [
                "labels" => [
                    "name" => __("Faqs"), // change the name
                    "singular_name" => __("Faq"), // change the name
                ],
                "public" => true,
                "supports" => ["title", "editor", "custom-fields", "page-attributes", "thumbnail",], // do you need all of these options?
                "taxonomies" => ["category", "post_tag"], // do you need categories and tags?
                "hierarchical" => true,
                "menu_icon" => get_bloginfo("template_directory") . "/images/icon.png",
                "rewrite" => ["slug" => __("faqs")], // change the name
            ]
        );
        register_post_type(
            "opportunities", // change the name
            [
                "labels" => [
                    "name" => __("Pracovní příležitosti"), // change the name
                    "singular_name" => __("opportunity"), // change the name
                ],
                "public" => true,
                "supports" => ["title", "editor", "custom-fields", "page-attributes", "thumbnail",], // do you need all of these options?
                "taxonomies" => ["category", "post_tag"], // do you need categories and tags?
                "hierarchical" => true,
                "menu_icon" => get_bloginfo("template_directory") . "/images/icon.png",
                "rewrite" => ["slug" => __("kariera")], // change the name
            ]
        );
    }
    add_action("init", "create_post_type");
endif; // ####
// function get_random_ehub_number()
// {
//     global $wpdb;
//     $ehublength = 10;
//     $ehubRandomNumber = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 1, $ehublength);
//     $exDetails = $wpdb->get_results("SELECT * FROM ehubformincrement where ehubNumber ='" . $ehubRandomNumber . "'", OBJECT);
//     if (!$wpdb->num_rows) {
//         $inserted = $wpdb->insert("ehubformincrement", ["ehubNumber" => $ehubRandomNumber,], ["%s"]);
//         return $ehubRandomNumber;
//     } else {
//         get_random_ehub_number();
//     }
// }
// add_action("wpcf7_init", "custom_add_form_tag_orderClock");
// function custom_add_form_tag_orderClock()
// {
//     wpcf7_add_form_tag("clockorder", "custom_clockorder_form_tag_handler"); // "clock" is the type of the form-tag

// }
// function custom_clockorder_form_tag_handler($tag)
// {
//     $newid = get_random_ehub_number();
//     $atts = ["type" => "text", "name" => "clockorder", "value" => $newid,];
//     $input = sprintf("<input %s />", wpcf7_format_atts($atts));
//     return $input; /*. $datalist;*/
// }

// JASS CHANGES
function get_random_ehub_number()
{
    global $wpdb;
    $ehublength = 10;
    $ehubRandomNumber = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $ehublength);

    $exDetails = $wpdb->get_results(
        $wpdb->prepare("SELECT id FROM ehubformincrement WHERE ehubNumber = %s", $ehubRandomNumber)
    );

    if (empty($exDetails)) {
        return $ehubRandomNumber;
    } else {
        return get_random_ehub_number(); // <-- return here is important
    }
}

add_action("wpcf7_init", "custom_add_form_tag_orderClock");
function custom_add_form_tag_orderClock()
{
    wpcf7_add_form_tag("clockorder", "custom_clockorder_form_tag_handler");
}

function custom_clockorder_form_tag_handler($tag)
{
    $newid = get_random_ehub_number();
    $atts = [
        "type"  => "text",
        "name"  => "clockorder",
        "value" => $newid,
    ];
    return sprintf("<input %s />", wpcf7_format_atts($atts));
}
//END JASS CHANGES
/*17dec*/
/*pagination*/
function energo_pagination($custom_query)
{
    $total_pages = $custom_query->max_num_pages;
    $big = 999999999; // need an unlikely integer
    if ($total_pages > 1) {
        $current_page = max(1, get_query_var("paged"));
        echo paginate_links(["base" => str_replace($big, "%#%", esc_url(get_pagenum_link($big))), "format" => "?paged=%#%", "current" => $current_page, "total" => $total_pages,]);
    }
}
function get_breadcrumb()
{
    echo '<a href="' . home_url() . '" rel="nofollow">Home</a>';
    if (is_category() || is_single()) {
        echo "&nbsp;&nbsp;&#187;&nbsp;&nbsp;";
        the_category(" &bull; ");
        if (is_single()) {
            echo " &nbsp;&nbsp;&#187;&nbsp;&nbsp; ";
            the_title();
        }
    } elseif (is_page()) {
        echo "&nbsp;&nbsp;&#187;&nbsp;&nbsp;";
        echo the_title();
    } elseif (is_search()) {
        echo "&nbsp;&nbsp;&#187;&nbsp;&nbsp;Search Results for... ";
        echo '"<em>';
        echo the_search_query();
        echo '</em>"';
    }
}
add_filter("get_the_archive_title", function ($title) {
    if (is_category()) {
        $title = single_cat_title("", false);
    } elseif (is_tag()) {
        $title = single_tag_title("", false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . "</span>";
    } elseif (is_tax()) {
        //for custom post types
        $title = sprintf(__('%1$s'), single_term_title("", false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title("", false);
    }
    return $title;
});
function add_query_vars($new_var)
{
    $new_var[] = "mycat";
    return $new_var;
}
add_filter("query_vars", "add_query_vars");
// Make Courses posts show up in archive pages
add_action("pre_get_posts", "wpshout_add_custom_post_types_to_query");
function wpshout_add_custom_post_types_to_query($query)
{
    if (is_archive()) {
        $mycat = get_query_var("mycat");
        //echo get_query_var('category_name');
        //$query->set('order', 'ASC');
        /* if($mycat) {
            $query->set('relation','AND');
        $query->set( 'category_name', $mycat );
        
        }
        */
    }
}
function mg_news_pagination_rewrite()
{
    add_rewrite_rule(get_option("category_base") . '/page/?([0-9]{1,})/?$', "index.php?pagename=" . get_option("category_base") . '&paged=$matches[1]', "top");
}
add_action("init", "mg_news_pagination_rewrite");
/*nav*/
if (!function_exists("twenty_twenty_one_the_posts_navigation")) {
    /**
     * Print the next and previous posts navigation.
     *
     * @since Twenty Twenty-One 1.0
     *
     * @return void
     */
    function twenty_twenty_one_the_posts_navigation()
    {
        the_posts_pagination(["before_page_number" => esc_html__("Stránka", "twentytwentyone") . " ", "mid_size" => 0, "prev_text" => sprintf('%s <span class="nav-prev-text">%s</span>', is_rtl() ? twenty_twenty_one_get_icon_svg("ui", "arrow_right") : twenty_twenty_one_get_icon_svg("ui", "arrow_left"), wp_kses(__('Novější <span class="nav-short">příspěvky</span>', "twentytwentyone"), ["span" => ["class" => [],],])), "next_text" => sprintf('<span class="nav-next-text">%s</span> %s', wp_kses(__('Starší <span class="nav-short">příspěvky</span>', "twentytwentyone"), ["span" => ["class" => [],],]), is_rtl() ? twenty_twenty_one_get_icon_svg("ui", "arrow_left") : twenty_twenty_one_get_icon_svg("ui", "arrow_right")),]);
    }
}

add_action("pre_get_posts", "mp_design_cat_posts_per_page");
function mp_design_cat_posts_per_page($query)
{
    $meta_query[] = $query->get('meta_query');
    $date_query[] = $query->get('date_query');
    $idObj = get_category_by_slug("slovnik-pojmu");
    $mycatid = "-" . $idObj->term_id;
    if ($query->is_archive() && $query->is_main_query() && !is_category("slovnik-pojmu")) {
        $query->set("cat", $mycatid);
    }
    if ($query->is_home() && $query->is_main_query() && !is_category("slovnik-pojmu")) {
        $query->set("cat", $mycatid);
    }
    if ($query->is_main_query() && is_category("slovnik-pojmu")) {
        $query->set("posts_per_page", "10");
        $query->set("meta_key", "post_count");
        $query->set("order", "DESC");
        $query->set("orderby", "meta_value_num");
    }
    if ($query->is_main_query() && is_category("nejhledanejsi")) {
        $query->set("posts_per_page", "10");
        $query->set("meta_key", "post_count");
        $query->set("order", "DESC");
        $query->set("orderby", "meta_value_num");
    }
    if ($query->is_post_type_archive('reference') && !is_admin() && $query->is_main_query()) {
        $query->set('posts_per_page', '51');

        if (!empty($_GET['zarizeni'])) {
            $meta_query[] = array(
                'key' => 'refrences_installation_type',
                'value' => $_GET['zarizeni'],
                'compare' => '=',
            );
            $query->set('meta_query', $meta_query);
        }
        if (!empty($_GET['druh'])) {
            $meta_query[] = array(
                'key' => 'druh_instalace',
                'value' => $_GET['druh'],
                'compare' => '=',
            );
            $query->set('meta_query', $meta_query);
        }
        if (!empty($_GET['lokalita'])) {
            $meta_query[] = array(
                'key' => 'kraja',
                'value' => $_GET['lokalita'],
                'compare' => '=',
            );
            $query->set('meta_query', $meta_query);
        }
        if (!empty($_GET['mesic'])) {
            $date = date('Y-m-d');
            $filter_date = $_GET['mesic'];
            if ($filter_date == '1-mesic') {
                $filter_criteria = date('M d, Y', strtotime("-1 month", strtotime($date)));
            }
            if ($filter_date == '3-mesic') {
                $filter_criteria = date('M d, Y', strtotime("- 3 months", strtotime($date)));
            }
            if ($filter_date == '6-mesic') {
                $filter_criteria = date('M d, Y', strtotime("- 6 months", strtotime($date)));
            }
            $date_query[] = array(
                'after' => $filter_criteria,
                /*'before'    => 'December 31st, 2023',*/
                'inclusive' => true,
            );
            $query->set('date_query', $date_query);
        }
    }

    if ($query->is_post_type_archive('grants') && !is_admin() && $query->is_main_query()) {
        $query->set('posts_per_page', '51');
        $query->set('order', 'ASC');
        if (!empty($_GET['seřadit'])) {
            if ($_GET['seřadit'] == "názvu") {
                $query->set('orderby', 'title');
            }
            if ($_GET['seřadit'] == "datum-vypsání") {
                $query->set('orderby', 'meta_value');
                $query->set('meta_key', 'start_date');
            }
            if ($_GET['seřadit'] == "datum-konce") {
                $query->set('orderby', 'meta_value');
                $query->set('meta_key', 'end_date');
            }
        } else {
            $query->set('orderby', 'title');
        }
        if (!empty($_GET['příjemce'])) {
            $meta_query[] = array(
                'key' => 'recipients',
                'value' => $_GET['příjemce'],
                'compare' => '=',
            );
            $query->set('meta_query', $meta_query);
        }
        if (!empty($_GET['zaměření'])) {
            $meta_query[] = array(
                'key' => 'focus',
                //'value' => 'fotovoltaika',
                //'value' => array('"' . $_GET['zaměření'] . '"'),
                //'value' => array ( 'fotovoltaika', 'ostatní' ),
                'value' => '"' . $_GET['zaměření'] . '"',
                'compare' => 'LIKE',
            );
            $query->set('meta_query', $meta_query);
        }
        if (!empty($_GET['demo'])) {
            $meta_query[] = array(
                'key' => 'checkbox',
                'value' => array('check1', 'check2', 'check3'),
                'compare' => '=',
            );
            $query->set('meta_query', $meta_query);
        }
		if($_GET['platnost'] != 'vse'){
			if (!empty($_GET['platnost'])) {
				$meta_query[] = array(
					'key' => 'validity',
					'value' => $_GET['platnost'],
					'compare' => '=',
				);
				$query->set('meta_query', $meta_query);
			}else{
				$meta_query[] = array(
					'key' => 'validity',
					'value' => 'aktivní',
					'compare' => '=',
				);
				$query->set('meta_query', $meta_query);
			}
		}
    }
    // print_r('<pre>');
    // print_r($query);
    // print_r('</pre>');
    // exit;

    return $query;
}
function wpse126157_comment_form_fields($fields)
{
    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $user_identity = $user->exists() ? $user->display_name : "";
    $args = wp_parse_args($args);
    if (!isset($args["format"])) {
        $args["format"] = current_theme_supports("html5", "comment-form") ? "html5" : "xhtml";
    }
    $req = get_option("require_name_email");
    $html_req = $req ? " required='required'" : "";
    $html5 = "html5" === $args["format"];
    $fields = [
        "author" => '<p class="comment-form-author">' . '<label for="author">' . __("Jméno") . ($req ? ' <span class="required">*</span>' : "") . "</label> " . '<input id="author" name="author" type="text" value="' . esc_attr($commenter["comment_author"]) . '" size="30" maxlength="245"' . $html_req . " /></p>",
        "email" => '<p class="comment-form-email"><label for="email">' . __("E-mail") . ($req ? ' <span class="required">*</span>' : "") . "</label> " . '<input id="email" name="email" ' . ($html5 ? 'type="email"' : 'type="text"') . ' value="' . esc_attr($commenter["comment_author_email"]) . '" size="30" maxlength="100" aria-describedby="email-notes"' . $html_req . " /></p>",
        /* 'url'     => '<p class="comment-form-url"><label for="url">' . __( 'Website' ) . '</label> ' .
         '<input id="url" name="url" ' . ( $html5 ? 'type="url"' : 'type="text"' ) . ' value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" maxlength="200" /></p>',*/
    ];
    return $fields;
}
add_filter("comment_form_default_fields", "wpse126157_comment_form_fields");
/*17dec*/
function modifying_single_template($template)
{
    //echo "I am here";
    //print_r(get_post_type());
    global $post;
    if ("faq" == get_post_type()) {
        //echo "now here";
        $template = dirname(__FILE__) . "/faq-special-template.php";
    }
    if ("opportunities" == get_post_type()) {
        //echo "now here";
        $template = dirname(__FILE__) . "/opportunity-special-template.php";
    }
    return $template;
}
add_filter("single_template", "modifying_single_template");
function tatwerat_startSession()
{
    if (!session_id()) {
        session_start();
    }
}
add_action("init", "tatwerat_startSession", 1);
add_action("wp_ajax_check_file_upload_session", "check_file_upload_session_callback");
add_action("wp_ajax_nopriv_check_file_upload_session", "check_file_upload_session_callback");
function check_file_upload_session_callback()
{
    $response = [];
    if (!empty($_SESSION["file_uploaded"])) {
        $response = $_SESSION["file_uploaded"];
    }
    echo json_encode($response);
    exit();
}
add_action("wp_ajax_file_upload", "file_upload_callback");
add_action("wp_ajax_nopriv_file_upload", "file_upload_callback");
function file_upload_callback()
{
    /* global $wp_filesystem;
     WP_Filesystem();*/
    $arrayResponse = array();
    check_ajax_referer("file_upload", "security");
    $arr_file_ext = ["image/png", "image/jpeg", "image/jpg", "image/gif", "application/pdf", "application/vnd.openxmlformats-officedocument.presentationml.presentation", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/msword", "application/vnd.ms-powerpoint,application/vnd.ms-excel", "application/rtf", "text/plain",];
    $upload_dir = wp_upload_dir();
    if (!empty($_FILES) && isset($_FILES['file'])) {
        $arrayResponse = array();
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            if (in_array($_FILES["file"]["type"][$i], $arr_file_ext)) {
                /*$upload = wp_upload_bits($_FILES["file"]["name"], null, file_get_contents($_FILES["file"]["tmp_name"]));*/
                //$upload['url'] will gives you uploaded file path
                if (!empty($upload_dir['basedir'])) {
                    $user_dirname = $upload_dir['basedir'] . '/karierresume';
                    if (!file_exists($user_dirname)) {
                        wp_mkdir_p($user_dirname);
                    }
                    $uploadedFileName = wp_unique_filename($user_dirname, $_FILES['file']['name'][$i]);
                    move_uploaded_file($_FILES['file']['tmp_name'][$i], $user_dirname . '/' . $uploadedFileName);
                    // save into database $upload_dir['baseurl'].'/cxc-images/'.$filename;
                }
            }
            $file_url = home_url() . "/wp-content/uploads/karierresume/" . $uploadedFileName;
            $arrayResponse[] = array(
                'uploadedFileName' => $uploadedFileName,
                'uploadedFileUrl' => $file_url,
            );
            // $uploadedFileName = basename($upload["file"]) . PHP_EOL;
        }
    }
    //$upload_dir["url"]."/".$uploadedFileName;
    if (!empty($arrayResponse)) {
        $_SESSION['file_uploaded'] = $arrayResponse;
        echo json_encode($arrayResponse);
    }
    exit();
}

add_action('wpcf7_before_send_mail', 'some_function_name', 1);
function some_function_name($contact_form)
{
    $wpcf7 = WPCF7_ContactForm::get_current();
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = array();
        $data['posted_data'] = $submission->get_posted_data();
        $firstName = $data['posted_data']['resumeFile']; // just enter the field name here
        $mail = $wpcf7->prop('mail');
        $new_val = explode(",", $firstName);
        foreach ($new_val as $key => $sin_resumeFile) {
            $html .= 'Kliknutí stáhnete životopis: <a href="' . $sin_resumeFile . '" target = "_blank" >resume</a><br/>';
        }
        if ($new_val != '') {
            $mail['body'] = str_replace('[resumeFile]', $html, $mail['body']);
        }
        $wpcf7->set_properties(
            array(
                "mail" => $mail
            )
        );
        return $wpcf7;
    }
}
/**
 * Add automatic image sizes
 */
if (function_exists('add_image_size')) {
    add_image_size('energo-slider-img', 298, 200, false); //(scaled)
    add_image_size('energo-thumb-img', 400, 320, true); //(cropped)
}
/*new function*/

add_action('wp_footer', 'mycustom_wp_footer');
function mycustom_wp_footer()
{
    ?>
<script type="text/javascript">
document.addEventListener('wpcf7mailsent', function(event) {
    if ('891' == event.detail.contactFormId) { // Change 34 to the ID of the form 
        //this is the bootstrap modal popup id
        alert('formulář byl úspěšně odeslán');
        sessionStorage.clear();
        location.reload();
    }
    if ('1846' == event.detail.contactFormId) {
        window.location.assign('<?php echo home_url('/dekujeme'); ?>');
    }
}, false);
document.addEventListener('wpcf7mailsent', function(event) {
    $('.wpcf7-response-output').addClass('alert alert-success');
}, false);

//Code by Jass
document.addEventListener('wpcf7mailsent', function(event) {
    // Check if it's your popup form by ID
    if (event.detail.contactFormId == '6656') {

        var clockOrderField = event.target.querySelector('input[name="clockorder"]');
        var clockOrderValue = clockOrderField ? clockOrderField.value : '';

        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            'event': 'formSubmit',
            'formType': 'Contact us',
            'formPosition': 'Popup',
            'testorderid': clockOrderValue  // <-- added this
        });

        console.log("GTM Event pushed for Popup Form with testorderid:", clockOrderValue);

        if (clockOrderValue) {
            sendEhubSale(clockOrderValue);
        }
    }
}, false);
//End Code by Jass
</script>
<script>
function sendEhubSale(testorderid) {
  // Add tracking pixel
  var img = document.createElement("img");
  img.id = "ehp";
  img.src = "https://ehub.cz/system/scripts/sale.php?campaignId=bfa8464c&orderId=" + testorderid;
  img.width = 1;
  img.height = 1;
  img.style.display = "none";
  document.body.appendChild(img);

  // Add Ehub sale script
  var script = document.createElement("script");
  script.id = "ehsjs";
  script.src = "https://ehub.cz/system/scripts/sale.js.php";
  script.async = true;
  script.defer = true;
  script.onload = function () {
    var sale = new EhubSale();
    sale.setCampaignId("bfa8464c");
    sale.setOrderId(testorderid);
    sale.process();
  };
  document.head.appendChild(script);
}
</script>
<?php }

add_action("wp_ajax_clear_file_upload_session", "clear_file_upload_session_callback");
add_action("wp_ajax_nopriv_clear_file_upload_session", "clear_file_upload_session_callback");
function clear_file_upload_session_callback()
{
    $response = [];
    $response['msg'] = "sent successfully";
    unset($_SESSION['file_uploaded']);
    echo json_encode($response);
    exit();
}


function getPostViews($postID)
{
    $count_key = 'post_count';
    $count = get_post_meta($postID, $count_key, true);
    if ($count == '') {
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return "0";
    }
    return $count;
}// r s p v a r j a s b i k
function setPostViews($postID)
{
    $count_key = 'post_count';
    $count = get_post_meta($postID, $count_key, true);
    if ($count == '') {
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    } else {
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}

function defer_parsing_of_js($url)
{
    if (is_user_logged_in())
        return $url; //don't break WP Admin
    if (FALSE === strpos($url, '.js'))
        return $url;
    if (strpos($url, 'jquery.js'))
        return $url;
    if (is_admin()) {
        return $tag;
    }
    return str_replace(' src', ' async defer src', $url);
}
//add_filter( 'script_loader_tag', 'defer_parsing_of_js', 10 );
// add_filter('wpcf7_spam', function () {
//     return false;
// });


add_filter('wpcf7_spam', function($spam, $submission) {    
	return $spam;
}, 10, 2);

add_filter('gform_required_legend', function ($legend, $form) {
    return '<span class="gfield_required gfield_required_asterisk">*</span> Povinné položky';
}, 10, 2);

add_filter("gform_validation_message", "change_message", 10, 2);
function change_message($message, $form)
{
    return '<h2 class="gform_submission_error hide_summary"><span class="gform-icon gform-icon--close"></span>Vyskytl se problém s vaším odesláním. Zkontrolujte prosím níže uvedená pole. 
Nebo nám napište email na <a href="mailto:info@energosolar.cz">info@energosolar.cz</a>. Děkujeme.</h2>';
}
// function create_careerformincrement_table() {
//     global $wpdb;
//     $table_name = 'careerformincrement';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE IF NOT EXISTS $table_name (
//         id bigint(20) NOT NULL AUTO_INCREMENT,
//         PRIMARY KEY (id)
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// }

// add_filter('gform_field_value_dynamic_id', 'gf_dynamic_id');
// function gf_dynamic_id($value)
// {
//     create_careerformincrement_table();
    
//     global $wpdb;
//     $table_name = 'careerformincrement';

//     // Get the maximum ID from the database
//     $exDetails = $wpdb->get_results("SELECT max(id) as maxId FROM $table_name", OBJECT);
//     $exDetail = $exDetails[0];
//     $maxId = $exDetail->maxId;

//     // If no ID exists yet, start from 2000
//     if (is_null($maxId)) {
//         $maxId = 1999; // Start from 2000
//     }

//     // Increment the ID
//     $newId = $maxId + 1;

//     // Return the new ID
//     return $newId;
// }

// add_action('gform_after_submission', 'increment_career_form_id', 10, 2);
// function increment_career_form_id($entry, $form)
// {
//     // The actual ID of your career form
//     $career_form_id = 1;

//     if ($form['id'] == $career_form_id) {
//         global $wpdb;
//         $table_name = 'careerformincrement';

//         // Get the maximum ID from the database
//         $exDetails = $wpdb->get_results("SELECT max(id) as maxId FROM $table_name", OBJECT);
//         $exDetail = $exDetails[0];
//         $maxId = $exDetail->maxId;

//         // If no ID exists yet, start from 2000
//         if (is_null($maxId)) {
//             $newId = 2000;
//         } else {
//             $newId = $maxId + 1;
//         }

//         // Insert the new ID into the database
//         $wpdb->insert($table_name, ["id" => $newId], ["%d"]);
//     }
// }

// add_filter('gform_pre_submission_filter', 'populate_hidden_field');
// function populate_hidden_field($form) {
//     // Populate the hidden field with the dynamic ID
//     foreach ($form['fields'] as &$field) {
//         if ($field->id == 8) { // Assuming your hidden field ID is 8
//             $field->defaultValue = apply_filters('gform_field_value_dynamic_id', '');
//         }
//     }
//     return $form;
// }

function create_careerformincrement_table() {
    global $wpdb;
    $table_name = 'careerformincrement';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// function gf_dynamic_id() {
//     static $cached_id = null; // <-- prevents multiple inserts per request

//     if ($cached_id !== null) {
//         return $cached_id; // reuse the ID
//     }

//     create_careerformincrement_table();

//     global $wpdb;
//     $table_name = 'careerformincrement';

//     // Set AUTO_INCREMENT if table empty
//     $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
//     if ($count === 0) {
//         $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 2000");
//     }

//     // Insert row ONCE
//     $inserted = $wpdb->query("INSERT INTO $table_name () VALUES ()");
//     if ($inserted === false) {
//         // fallback: get MAX(id)+1
//         $cached_id = (int) $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
//     } else {
//         $cached_id = $wpdb->insert_id;
//     }

//     return $cached_id;
// }

// Pre-fill hidden field on render & validation
// add_filter('gform_pre_render', 'populate_hidden_field');
// add_filter('gform_pre_validation', 'populate_hidden_field'); // for AJAX validation

// function populate_hidden_field($form) {
//     foreach ($form['fields'] as &$field) {
//         if ($field->id == 8) {
//             $field->defaultValue = gf_dynamic_id(); // <-- inserts only once now
//         }
//     }
//     return $form;
// }

function gf_generate_increment_number() {
    create_careerformincrement_table();
    global $wpdb;
    $table_name = 'careerformincrement';

    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($count === 0) {
        $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 2000");
    }

    $wpdb->query("INSERT INTO $table_name () VALUES ()");
    return $wpdb->insert_id;
}

add_action('gform_pre_submission', function($form) {
    // Only for your form
    if ($form['id'] != 1) return;

    $field_id = 8; // hidden field ID

    // Only generate a number if the field is empty (prevents double increment)
    if (empty($_POST["input_{$field_id}"])) {
        $unique_number = gf_generate_increment_number();
        $_POST["input_{$field_id}"] = $unique_number;
    }
});



function wpcf7_input_numbers_only()
{
    echo '
  <script>
  onload =function(){ 
    var ele = document.querySelectorAll(\'.wpcf7-numbers-only\')[0];
    ele.onkeypress = function(e) {
      if(isNaN(this.value+""+String.fromCharCode(e.charCode)))
      return false;
    }
    ele.onpaste = function(e){
      e.preventDefault();
    }
  }
  </script>
  ';
}

add_filter('wpcf7_validate_text*', 'custom_text_validation_filter', 20, 2);

function custom_text_validation_filter($result, $tag)
{
    if ('jmeno' == $tag->name) {
        // matches any utf words with the first not starting with a number
        $re = '/^[A-Za-z0-9À-úÀ-ÿÀ-ÖØ-öø-ÿǍ-Ž ]+$/';

        if (!preg_match($re, $_POST['jmeno'], $matches)) {
            $result->invalidate($tag, "Toto není platné jméno!");
        }
    }

    return $result;
}

function my_acf_load_value($value, $post_id, $field)
{
    // vars
    $order = array();
    // bail early if no value
    if (empty($value)) {
        return $value;
    }
    // populate order
    foreach ($value as $i => $row) {
        $order[$i] = $row['field_6576813d61495']; // Ersetzen Sie 'field_XXXXX' durch den tatsächlichen Schlüssel des 'regie'-Feldes.
    }
    // multisort
    array_multisort($order, SORT_DESC, $value); // Ändern Sie SORT_DESC in SORT_ASC, wenn Sie aufsteigend sortieren möchten.
    // return   
    return $value;
}
add_filter('acf/load_value/name=testimonial_slider', 'my_acf_load_value', 10, 3); // Ändern Sie 'scores' in 'bestfilm'.

function cc_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

add_filter('xmlrpc_enabled', '__return_false');


function myprefix_exclude_ipad($is_mobile)
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
        $is_mobile = false;
    }
    return $is_mobile;
}
add_filter('wp_is_mobile', 'myprefix_exclude_ipad');

add_action('wp_footer', 'form_submit_btn_disable');
function form_submit_btn_disable()
{
    ?>
<script type="text/javascript">
jQuery('.wpcf7-form').submit(function() {
    let pri_time = parseInt($('#pre_time').val(), 10);
    let current_time = Date.now();
    let time_diff = current_time - pri_time;
    if (time_diff < 3000) {
        event.preventDefault();
        return;
    }
    jQuery(this).find(':input[type=submit]').prop('disabled', true);
    var wpcf7Elm = document.querySelector('.wpcf7');
    wpcf7Elm.addEventListener('wpcf7submit', function(event) {
        jQuery('.wpcf7-submit').prop("disabled", false);
    }, false);
    wpcf7Elm.addEventListener('wpcf7invalid', function() {
        jQuery('.wpcf7-submit').prop("disabled", false);
    }, false);
});
</script>
<?php }

function check_time_spam($spam, $submission) {
    $posted_data = $submission->get_posted_data();
    
    if(isset($posted_data['submission-time'])) {
        $submission_time = intval($posted_data['submission-time']) / 1000;
        $current_time = time();
        $time_difference = $current_time - $submission_time;
        
        if ($time_difference < 3) { 
            $spam = true;
            
            add_filter('wpcf7_display_message', function($message, $status) {
                if ($status === 'spam') {
                    return wpcf7_get_message('mail_sent_ok');
                }
                return $message;
            }, 10, 2);
        }
    }
    
    return $spam;
}

add_filter('wpcf7_skip_mail', 'iw_skip_spam');
function iw_skip_spam()
{
    $submission = WPCF7_Submission::get_instance();
    // We're skipping spam check later. This makes Honeypot for CF7 work.
    if (defined('HONEYPOT4CF7_PLUGIN')) {
        if (true == honeypot4cf7_spam_check(false, $submission)) {
            return true;
        }
    }
    $form_data = implode(
        ' ',
        wpcf7_array_flatten($submission->get_posted_data())
    );
    // Auto spam any Russian characters
    if (
        preg_match(
            '/yandex/',
            $form_data
        )
    ) {
        return true;
    }
    $form_data = preg_replace('/[^a-z0-9]+/i', ' ', strtolower($form_data));
    $form_data = preg_replace('/\s+/', ' ', $form_data);
    $form_data = explode(' ', $form_data);

    // From Settings -> Discussion -> Disallowed Comment Keys
    $bad_words = get_option('disallowed_keys');
    if (empty($bad_words)) {
        return false;
    }
    $bad_words = explode("\n", trim($bad_words));
    foreach ($bad_words as $word) {
        $word = trim(strtolower($word));
        if (strlen($word) < 3) {
            continue;
        }
        if (in_array($word, $form_data)) {
            return true;
        }
    }
    return false;
}

// Custom comment structure 
function mytheme_comment($comment, $args, $depth)
{
    if ('div' === $args['style']) {
        $tag = 'div';
        $add_below = 'comment';
    } else {
        $tag = 'li';
        $add_below = 'div-comment';
    }
    $classes = ' ' . comment_class(empty($args['has_children']) ? '' : 'parent', null, null, false);
    ?>
<<?= $tag . $classes; ?> id="comment-<?php comment_ID() ?>">
    <?php if ('div' != $args['style']) { ?>
    <div id="div-comment-<?php comment_ID() ?>" class="comment-body"><?php
    } ?>
        <div class="comment-author vcard">
            <?php
        if ($args['avatar_size'] != 0) {
            echo get_avatar($comment, $args['avatar_size']);
        }
        else{
            echo '<div class="avatar-img-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg></div>';
        }
        printf(
            __('<cite class="fn">%s</cite> <span class="says">says:</span>'),
            get_comment_author_link()
        );
        ?>
            <div class="reply">
                <?php
            comment_reply_link(
                array_merge(
                    $args,
                    array(
                        'add_below' => $add_below,
                        'reply_text' => __('Odpovědět <span>&darr;</span>', 'textdomain'),
                        'depth' => $depth,
                        'max_depth' => $args['max_depth']
                    )
                )
            ); ?>
            </div>
        </div>
        <?php if ($comment->comment_approved == '0') { ?>
        <em class="comment-awaiting-moderation">
            <?php _e('Váš komentář čeká na moderaci.'); ?>
        </em><br />
        <?php } ?>
        <div class="comment-meta commentmetadata">
            <a href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>">
                <?php
            printf(
                __('%1$s at %2$s'),
                get_comment_date('d\.m\.Y'),
                get_comment_time()
            ); ?>
            </a>
            <?php edit_comment_link(__('(Edit)'), '  ', ''); ?>
        </div>
        <?php comment_text(); ?>
        <?php if ('div' != $args['style']) { ?>
    </div>
    <?php }
}
//Disable HTML in comments
function convert_comment_html_entities($comment_text)
{
    $comment_text = htmlspecialchars($comment_text);
    $comment_text = make_clickable($comment_text);
    return $comment_text;
}
function disable_comment_links($comment_text)
{
    $comment_text = strip_tags($comment_text);
    return $comment_text;
}
add_filter('comment_text', 'convert_comment_html_entities', 10, 1);
add_filter('comment_text', 'disable_comment_links', 20, 1);
// Hook to the filter
add_filter('wpseo_breadcrumb_links', 'wpse_332125_breadcrumbs');
// $links are the current breadcrumbs
function wpse_332125_breadcrumbs($links)
{
    // Use is_singular($post_type) to identify a single CPT
    // This assumes your CPT is called "project" - change it as needed
    if (is_singular('grants')) {
        // The first item in $links ($links[0]) is Home, so skip it
        // The second item in $links is Projects - we want to change that
        $links[1] = array('text' => 'Dotace', 'url' => '/dotace-vyzvy-dotacni-programy/?platnost=aktivní', 'allow_html' => 1);
    }
    // Even if we didn't change anything, always return the breadcrumbs
    return $links;
}
add_filter( 'auto_update_plugin', '__return_false' );
add_filter( 'auto_update_theme', '__return_false' );
function get_top_5_common_tags_from_post($post_id) {
    // Get all tags for the current post
    $post_tags = wp_get_post_tags($post_id);
    if (empty($post_tags)) {
        return []; // No tags found
    }
    $tag_frequencies = [];
    // Get all tags used on the site
    $all_tags = get_tags(array(
        'hide_empty' => false // Include all tags, even if they're not used in any post
    ));
    // Loop through the tags for the current post and count their usage across the site
    foreach ($post_tags as $post_tag) {
        foreach ($all_tags as $tag) {
            if ($post_tag->term_id === $tag->term_id) {
                $tag_frequencies[$tag->term_id] = [
                    'name' => $tag->name,
                    'count' => $tag->count,
                    'link' => get_tag_link($tag->term_id) // Get the link for the tag
                ];
            }
        }
    }
    // Sort the tags by usage count, descending
    usort($tag_frequencies, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    // Get the top 5 most used tags
    $top_5_tags = array_slice($tag_frequencies, 0, 5);
    return $top_5_tags;
}
    function custom_contact_form_shortcode()
    {
        ob_start();
        ?>
    <div class="wpcf7 js" id="wpcf7-f70-p538-o2" lang="en-US" dir="ltr">
        <div class="screen-reader-response">
            <p role="status" aria-live="polite" aria-atomic="true"></p>
            <ul></ul>
        </div>
        <!-- <form id="custom-contact-form" method="post" class="" aria-label="Contact form" novalidate="novalidate" data-status="init"> -->
        <form id="custom-contact-form" novalidate>
            <input type="hidden" name="_wpcf7">
            <input type="hidden" name="_wpcf7_version" value="5.9.6">
            <input type="hidden" name="_wpcf7_locale">
            <input type="hidden" name="_wpcf7_unit_tag">
            <input type="hidden" name="_wpcf7_container_post">
            <input type="hidden" name="_wpcf7_posted_data_hash">
            <input type="hidden" name="_wpcf7cf_hidden_group_fields">
            <input type="hidden" name="_wpcf7cf_hidden_groups">
            <input type="hidden" name="_wpcf7cf_visible_groups">
            <input type="hidden" name="_wpcf7cf_repeaters">
            <input type="hidden" name="_wpcf7cf_steps">
            <input type="hidden" name="_wpcf7cf_options">
            <input type="hidden" name="utm_campaign">
            <input type="hidden" name="utm_source">
            <input type="hidden" name="utm_medium">
            <input type="hidden" name="utm_term">
            <input type="hidden" name="utm_content">
            <input type="hidden" name="gclid">
            <input type="hidden" id="ak_js_2" name="_wpcf7_ak_js" value="1733739152948">
            <input type="hidden" name="form_origin" id="form_origin" value="">
            <input type="hidden" name="custom_nonce_custom_form"
                value="<?php echo wp_create_nonce('custom_contact_form_nonce'); ?>">
            <input type="hidden" name="form_load_time" value="<?php echo time(); ?>">
            <input type="text" name="bot_trap" value="" style="display:none;" autocomplete="off">
            <input type="hidden" name="custom_recaptcha_token" id="custom_recaptcha_token" value="
                ">
            <div class="row">
                <!-- row -->
                <span style="display:none;"><input type="text" name="clockorder" value="<?php echo esc_attr( get_random_ehub_number() ); ?>"> </span>
                <div class="mob-order">
                    <div class="col-md-6 mob-second">
                        <p><label for="zaka">Typ zakázky <em>*</em></label>
                            <select id="zakapro" name="zaka" required data-error="Vyberte jednu z položek ze seznamu">
                                <option value="">Vyberte</option>
                                <option value="Firmy a průmysl">Firmy a průmysl</option>
                                <option value="SVJ a bytové domy">SVJ a bytové domy</option>
                                <option value="Komerční bateriová úložiště">Komerční bateriová úložiště</option>
                                <option value="VIP elektrárna/FVE s umělou inteligencí">VIP elektrárna/FVE s umělou
                                    inteligencí</option>
                                <option value="Fotovoltaika pro spotřebitele/domácnost">Fotovoltaika pro
                                    spotřebitele/domácnost
                                </option>
                                <option value="Tepelná čerpadla">Tepelná čerpadla</option>
                                <option value="Fotovoltaika s čerpadlem/elektronabíječkou">Fotovoltaika s
                                    čerpadlem/elektronabíječkou
                                </option>
                                <!--<option value="Ohřev vody (NZÚ Light)">Ohřev vody (NZÚ Light)</option>-->
                                <option value="Ostatní/jiné">Ostatní/jiné</option>
                                <!-- <option value="Ostatní/jinée" style="display:none">Ostatní/jinée</option> -->
                                <!-- <option value="OstatníDummy" style="display:none">OstatníDummy</option> -->
                            </select>
                        </p>
                    </div>
                    <div class="col-md-6 mob-first">
                        <div class="footerBadge" style="margin-bottom: 5px;">
                            <p>
                                <img class="img-fluid"
                                    src="https://www.energosolar.cz/wp-content/uploads/2025/07/certificatebadge.png">
                            </p>
                            <p class="cnt_txt">Patříme mezi nejlépe hodnocené společnosti na portálu Refsite
                            </p>
                        </div>
                    </div>
                </div>
            </div> <!-- /row -->
            <div id="company-checkbox-container">
                <input type="checkbox" id="show-company-name" class="form-check-input">
                <label id="company-checkbox-label" for="show-company-name">Chci uvést název firmy</label>
            </div>
            <div id="company-row" class="row" style="display:none">
                <!-- row -->
                <div class="col-md-12">
                    <p><label for="jmeno" id="company-label">Název firmy/Název SVJ</label>
                        <input type="text" name="firmy-svj" maxlength="20">
                    </p>
                </div>
            </div> <!-- /row -->
            <div class="row">
                <!-- row -->
                <div class="col-md-6">
                    <p><label for="jmeno">Jméno <em>*</em></label>
                        <input type="text" name="jmeno" maxlength="20" required data-error="Vyplňte prosím toto pole">
                    </p>
                </div>
                <div class="col-md-6">
                    <p><label for="prijmeni">Příjmení <em>*</em></label>
                        <input type="text" name="prijmeni" maxlength="80" required
                            data-error="Vyplňte prosím toto pole">
                    </p>
                </div>
            </div> <!-- /row -->
            <div class="row">
                <!-- row -->
                <div class="col-md-6 colRela">
                    <div id="HelpTele" class="help help--text">
                        <p><span class="help__question">Proč chcete telefon?</span>
                            <span class="help__answer"><span class="help__close"> </span>Pro co nejrychlejší dořešení
                                vaší poptávky a doplnění informací pro detailní cenovou kalkulaci, vám nejdříve zavoláme
                                (zpravidla do 24-48 hodin).</span>
                        </p>
                    </div>
                    <p><label for="telefon">Telefon <em>*</em></label>
                        <input type="tel" name="telefon" placeholder="+420" maxlength="80" required
                            data-error="Vyplňte prosím toto pole">
                    </p>
                </div>
                <div class="col-md-6">
                    <p><label for="email">E-mail <em>*</em></label>
                        <input type="email" name="email" placeholder="@" maxlength="80" required
                            data-error="Vyplňte prosím toto pole">
                    </p>
                </div>
            </div> <!-- /row -->
            <div class="row">
                <!-- row -->
                <div class="col-md-6">
                    <p><label for="kraj">Kraj <em>*</em></label>
                        <select class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required kraj_dropdown"
                            aria-required="true" aria-invalid="false" name="Kraj" data-error="Vyberte Kraj">
                            <option value="">Vyberte</option>
                            <option value="Hlavní město Praha">Hlavní město Praha</option>
                            <option value="Jihočeský kraj">Jihočeský kraj</option>
                            <option value="Jihomoravský kraj">Jihomoravský kraj</option>
                            <option value="Karlovarský kraj">Karlovarský kraj</option>
                            <option value="Královéhradecký kraj">Královéhradecký kraj</option>
                            <option value="Liberecký kraj">Liberecký kraj</option>
                            <option value="Moravskoslezský kraj">Moravskoslezský kraj</option>
                            <option value="Olomoucký kraj">Olomoucký kraj</option>
                            <option value="Pardubický kraj">Pardubický kraj</option>
                            <option value="Plzeňský kraj">Plzeňský kraj</option>
                            <option value="Středočeský kraj">Středočeský kraj</option>
                            <option value="Ústecký kraj">Ústecký kraj</option>
                            <option value="Vysočina kraj">Vysočina kraj</option>
                            <option value="Zlínský kraj">Zlínský kraj</option>
                            <option value="Nevím">Nevím</option>
                        </select>
                    </p>
                </div>
                <div class="col-md-6 colRela">
                    <div id="HelpOkres">
                        <span class="help help--text"> <span class="help__question">Proč chcete okres?</span> <span
                                class="help__answer"> <span class="help__close"> </span>Informace o okresu
                                minimalizujeme tak čas, který čekáte na nabídku. Díky této informaci k vám můžeme vždy
                                vyslat nejbližšího technického specialistu, aby s vámi zakázku prošel. </span>
                    </div>
                    <p><label for="okres">Okres <em>*</em></label>
                        <select disabled="disabled" class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required"
                            id="okres" aria-required="true" aria-invalid="false" name="Okres"
                            data-error="Vyberte Okres">
                            <option value="">Vyberte nejprve kraj</option>
                            <option value="Hlavní město Praha">Hlavní město Praha</option>
                            <option value="Benešov">Benešov</option>
                            <option value="Beroun">Beroun</option>
                            <option value="Blansko">Blansko</option>
                            <option value="Břeclav">Břeclav</option>
                            <option value="Brno-město">Brno-město</option>
                            <option value="Brno-venkov">Brno-venkov</option>
                            <option value="Bruntál">Bruntál</option>
                            <option value="Česká Lípa">Česká Lípa</option>
                            <option value="České Budějovice">České Budějovice</option>
                            <option value="Český Krumlov">Český Krumlov</option>
                            <option value="Cheb">Cheb</option>
                            <option value="Chomutov">Chomutov</option>
                            <option value="Chrudim">Chrudim</option>
                            <option value="Děčín">Děčín</option>
                            <option value="Domažlice">Domažlice</option>
                            <option value="Frýdek-Místek">Frýdek-Místek</option>
                            <option value="Havlíčkův Brod">Havlíčkův Brod</option>
                            <option value="Hodonín">Hodonín</option>
                            <option value="Hradec Králové">Hradec Králové</option>
                            <option value="Jablonec nad Nisou">Jablonec nad Nisou</option>
                            <option value="Jeseník">Jeseník</option>
                            <option value="Jičín">Jičín</option>
                            <option value="Jihlava">Jihlava</option>
                            <option value="Jindřichův Hradec">Jindřichův Hradec</option>
                            <option value="Karlovy Vary">Karlovy Vary</option>
                            <option value="Karviná">Karviná</option>
                            <option value="Kroměříž">Kroměříž</option>
                            <option value="Kladno">Kladno</option>
                            <option value="Klatovy">Klatovy</option>
                            <option value="Kolín">Kolín</option>
                            <option value="Kutná Hora">Kutná Hora</option>
                            <option value="Liberec">Liberec</option>
                            <option value="Litoměřice">Litoměřice</option>
                            <option value="Louny">Louny</option>
                            <option value="Most">Most</option>
                            <option value="Mělník">Mělník</option>
                            <option value="Mladá Boleslav">Mladá Boleslav</option>
                            <option value="Náchod">Náchod</option>
                            <option value="Nový Jičín">Nový Jičín</option>
                            <option value="Nymburk">Nymburk</option>
                            <option value="Olomouc">Olomouc</option>
                            <option value="Opava">Opava</option>
                            <option value="Ostrava-město">Ostrava-město</option>
                            <option value="Pardubice">Pardubice</option>
                            <option value="Pelhřimov">Pelhřimov</option>
                            <option value="Písek">Písek</option>
                            <option value="Plzeň-jih">Plzeň-jih</option>
                            <option value="Plzeň-město">Plzeň-město</option>
                            <option value="Plzeň-sever">Plzeň-sever</option>
                            <option value="Prachatice">Prachatice</option>
                            <option value="Praha-východ">Praha-východ</option>
                            <option value="Praha-západ">Praha-západ</option>
                            <option value="Příbram">Příbram</option>
                            <option value="Prostějov">Prostějov</option>
                            <option value="Přerov">Přerov</option>
                            <option value="Rakovník">Rakovník</option>
                            <option value="Rokycany">Rokycany</option>
                            <option value="Rychnov nad Kněžnou">Rychnov nad Kněžnou</option>
                            <option value="Semily">Semily</option>
                            <option value="Sokolov">Sokolov</option>
                            <option value="Strakonice">Strakonice</option>
                            <option value="Svitavy">Svitavy</option>
                            <option value="Šumperk">Šumperk</option>
                            <option value="Tábor">Tábor</option>
                            <option value="Tachov">Tachov</option>
                            <option value="Teplice">Teplice</option>
                            <option value="Třebíč">Třebíč</option>
                            <option value="Trutnov">Trutnov</option>
                            <option value="Uherské Hradiště">Uherské Hradiště</option>
                            <option value="Ústí nad Labem">Ústí nad Labem</option>
                            <option value="Ústí nad Orlicí">Ústí nad Orlicí</option>
                            <option value="Vsetín">Vsetín</option>
                            <option value="Vyškov">Vyškov</option>
                            <option value="Žďár nad Sázavou">Žďár nad Sázavou</option>
                            <option value="Znojmo">Znojmo</option>
                            <option value="Zlín">Zlín</option>
                            <option value="Nevím/nelze určit">Nevím/nelze určit</option>
                        </select>
                    </p>
                </div>
            </div> <!-- /row -->
            <div class="row align-items-top">
                <!-- row -->
                <div class="clearfix"></div>
                <div class=" col-md-12">
                    <label for="show-description" class="desc-text"><input type="checkbox" name="show-description"
                            id="show-description" value="1" class="form-check-input">Přidat poznámku nebo upřesnit vaše
                        představy</label>
                    <br>
                    <div id="msgsection" class="checkdesc" style="display:none;">
                        <textarea name="message" style="height: auto;" cols="40" rows="6" maxlength="5000" id="msg"
                            aria-required="false" aria-invalid="false" placeholder="Vaše poznámka"></textarea>
                        <span id="msgcount" data-starting-value="0" data-current-value="0"
                            data-maximum-value="5000">0</span> <span>/ 5000 znaků</span>
                    </div>
                    <label for="financovani" class="desc-text"><input type="checkbox" name="financovani" value="1"
                            class="form-check-input">Mám zájem o financování</label>
                    <span class="help help--text informac"> <span class="help__question">Více informací</span> <span
                            class="help__answer"> <span class="help__close"> </span>Pokud nemáte k dispozici celou výši
                            depozitu, můžeme vám zajistit i možnost bezstarostného dofinancování vaší fotovoltaické
                            elektrárny či tepelného čerpadla externí úvěrovou finanční službou. A to vše za velmi
                            výhodných podmínek.</span> </span><br>
                    <label for="emailoptout" class="desc-text"><input type="checkbox" name="emailoptout" value="1"
                            class="form-check-input">Souhlasím s užitím svých údajů pro marketingové účely</label>
                    <p class="myaccept form__note ps-1 mt-2">Odesláním formuláře uděluji souhlas společnosti Chaintech
                        s.r.o. ke <a href="/podminky-ochrany-osobnich-udaju" class="mlink">zpracování svých osobních
                            údajů </a> v&nbsp;souladu s&nbsp;evropským nařízením GDPR.</p>
                </div>
                <div class="col-lg-5 col-md-12 col-sm-12 mt-xl-1 text-center text-md-end">
                    <input name="honeypot" type="text" id="honeypot" style="display:none;" class="zip"
                        autocomplete="false">
                    <!--<div class="g-recaptcha" data-sitekey="6Le4STYrAAAAAJBfg9oZL1W_kS5LwkQ_UHP7iZNZ"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <div class="col-md-5 mt-xl-1 text-center text-md-end">
                        <div id="result"></div>-->
                    <input type="submit" class="wpcf7-form-control has-spinner wpcf7-submit btn btn-success energoBtn3"
                        value="Chci nabídku zdarma" style="margin-bottom:20px;">
                </div>
            </div>
			  <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('custom-contact-form');
    const emailField = form.querySelector('input[name="email"]');

    form.addEventListener('submit', function(e) {
        const email = emailField.value.trim().toLowerCase();

        // 🧠 Known disposable and fake email domains
        const spamDomains = [
            'mailinator.com', 'yopmail.com', 'tempmail.com', 'guerrillamail.com', 'sharklasers.com',
            '10minutemail.com', 'maildrop.cc', 'getnada.com', 'trashmail.com', 'mailnesia.com',
            'dispostable.com', 'fakeinbox.com', 'throwawaymail.com', 'spamgourmet.com',
            'example.com', 'test.com', 'mail-temporaire.fr', 'inboxkitten.com', 'mintemail.com',
            'temporarymail.com', 'moakt.com', 'guerillamail.net', 'anonbox.net', 'deadaddress.com'
        ];

        // 🧩 Suspicious name patterns and random/fake email structures
        const spamPatterns = [
            /^test/i,
            /fake/i,
            /spam/i,
            /asdf/i,
            /qwerty/i,
            /123/i,
            /000/i,
            /111/i,
            /zzz/i,
            /noreply/i,
            /no-reply/i,
            /temp/i,
            /trash/i,
            /bot/i,
            /dummy/i,
            /@example/,
            /@tempmail/,
            /\.ru$/,
            /\.xyz$/,
            /\.tk$/,
            /\.top$/,
            /\.pw$/,
            /\.click$/,
            /\.biz$/,
            /\.ml$/
        ];

        let isSpam = false;

        // 🚫 Domain check
        for (const domain of spamDomains) {
            if (email.includes(domain)) {
                isSpam = true;
                break;
            }
        }

        // 🚫 Pattern check
        for (const pattern of spamPatterns) {
            if (pattern.test(email)) {
                isSpam = true;
                break;
            }
        }

        // Clean up old message
        const existingError = form.querySelector('.email-error-msg');
        if (existingError) existingError.remove();

        // 🚨 Show error and block submission
        if (isSpam) {
            e.preventDefault();
            const msg = document.createElement('div');
            msg.textContent = 'E-mailová adresa není povolena.';
            msg.className = 'email-error-msg';
            emailField.insertAdjacentElement('afterend', msg);
            emailField.focus();
            return false;
        }
    });
});
</script>

    <style>
    .email-error-msg {
        color: #d00;
        font-size: 13px;
        margin-top: 4px;
    }
    </style>

            <style>
            .desc-text {
                font-size: 13px;
            }

            .btn-custom {
                padding: 8px 30px !important;
                background: #fff;
                background: #198754 !important;
                color: #fff !important;
                text-decoration: none !important;
                transition: all 0.3s ease-in-out;
                margin-left: 17px !important;
                border-radius: 28px !important;
                font-size: large !important;
                box-shadow: 0rem .3rem .7rem rgba(0, 0, 0, .17) !important;
                font-weight: 700 !important;
            }
            </style>
    </div>
    </form>
    </div>
    <?php
    return ob_get_clean();
    }
    add_shortcode('custom_contact_form_demo', 'custom_contact_form_shortcode');

    function get_user_ip() {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return filter_var(explode(',', $_SERVER[$key])[0], FILTER_VALIDATE_IP);
            }
        }
        return 'UNKNOWN';
    }
        function process_custom_contact_form_submission()
        {
            $output = $_POST;
            // parse_str(json_encode($output["form_data"]), $posted_data);
            parse_str($output["form_data"], $posted_data);

            $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();

            // inline logger (no new global function)
            $custom_logger = function($status, $message) use ($posted_data, $referer) {
                log_form_submission([
                'form_type'      => 'custom',
                'form_id'        => 'custom_form_main',
                'page_url'       => $referer,
                'status'         => $status,
                'error_message'  => $message,
                'submitted_data' => $posted_data,
                ]);
            };

            // Check nonce
            if (
                !isset($posted_data['custom_nonce_custom_form']) ||
                !wp_verify_nonce($posted_data['custom_nonce_custom_form'], 'custom_contact_form_nonce')
            ) {
                $custom_logger('error', 'Invalid security token (nonce).');
                wp_send_json_error(['message' => 'Invalid security token.']);
                wp_die();
            }

            // Honeypot trap: if filled, it's a bot
            if (!empty($posted_data['bot_trap'])) {
                wp_send_json_error(['message' => 'Zprávu nelze odeslat, protože jste nevyplnili všechny povinné údaje.']);
                wp_die();
            }

            $zaka = isset($posted_data["zaka"]) ? $posted_data["zaka"] : '';
            $zakapro = isset($zaka[0]) ? $zaka[0] : '';

            // Block bots
            $bot_trap_values = ['Ostatní/jinée', 'dummy', 'hiddenbot', 'botoption'];
            if (in_array(strtolower(trim($zakapro)), $bot_trap_values, true)) {
                wp_send_json_success([
                    'message' => 'Děkujeme, ale zpráva nebude odeslána.',
                    'botFiltered' => true
                ]);
                wp_die();
            }

            if ($posted_data['honeypot']>1) { 
                echo "Spam detected.";
                exit;
            }

            global $wpdb;
            $formData = [
                'zaka' => $posted_data['zaka'] ?? '',
                'jmeno' => $posted_data['jmeno'] ?? '',
                'prijmeni' => $posted_data['prijmeni'] ?? '',
                'firmy-sv' => $posted_data['firmy-svj'] ?? '',
                'telefon' => $posted_data['telefon'] ?? '',
                'email' => $posted_data['email'] ?? '',
                'Kraj' => $posted_data['Kraj'] ?? '',
                'Okres' => $posted_data['Okres'] ?? '',
                'show-description' => isset($posted_data['show-description']) ? true : false, // Checkbox
                'message' => $posted_data['message'] ?? '',
                'Current_Page' =>  $referer
            ];
            $errors = [];

            // BOT TRAP CHANGES
            
            $ip = get_user_ip();
            $now = time();
            $form_load_time = isset($posted_data['form_load_time']) ? (int)$posted_data['form_load_time'] : 0;
            $submission_duration = $now - $form_load_time;
            $email = sanitize_email($posted_data['email'] ?? '');
            $bot_trap = trim($posted_data['bot_trap'] ?? '');
            $firstname = isset($posted_data["jmeno"]) ? $posted_data["jmeno"] : '';
            $lastname = isset($posted_data["prijmeni"]) ? $posted_data["prijmeni"] : '';

            foreach ($formData as $field => $value) {
                if (in_array($field, ['message', 'show-description', 'firmy-sv'])) {
                    continue; // Already handled above
                }

                if (empty($value)) {
                    $errors[$field] = "Prosím, vyplňte toto pole.";
                }
            }
            if (!empty($errors)) {
                //$custom_logger('error', 'Validation failed: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
                wp_send_json_error(['errors' => $errors]);
                exit;
            }
            
            // 1. Bot Trap Filled
            if (!empty($bot_trap)) {
                error_log("[SPAM] Bot trap filled - IP: $ip, Email: $email");
                wp_send_json_success(['message' => 'Děkujeme, ale zpráva nebude odeslána.', 'botFiltered' => true]);
                wp_die();
            }
            
            // 2. Fast Submit (<8s)
            if ($submission_duration > 0 && $submission_duration < 12) {
                error_log("[SPAM] Fast submission - $submission_duration sec - IP: $ip, Email: $email");
                wp_send_json_success(['message' => 'Děkujeme, ale zpráva nebude odeslána.', 'botFiltered' => true]);
                wp_die();
            }
            
            // 3. Dummy Zakazka
            if (strtolower($zakapro) === 'ostatnídummy' || strtolower($zakapro) === 'ostatní/jinée') {
                error_log("[SPAM] Dummy zakazka selected - IP: $ip, Email: $email");
                wp_send_json_success(['message' => 'Děkujeme, ale zpráva nebude odeslána.', 'botFiltered' => true]);
                wp_die();
            }

            // Names containing numbers
            if (preg_match('/[0-9]/', $firstname) || preg_match('/[0-9]/', $lastname)) {
                $custom_logger('error', "[SPAM][numeric name] $ip, $email, $firstname $lastname");
                wp_send_json_success(['message' => "Zadání obsahuje neplatné znaky."]);
                wp_die();
            }

            // Names too short
            if (strlen($firstname) < 2 || strlen($lastname) < 2) {
                $custom_logger('error', "[SPAM][short name] $ip, $email, $firstname $lastname");
                wp_send_json_success(['message' => "Jméno nebo příjmení je příliš krátké."]);
                wp_die();
            }

            // Names with all caps
            if (preg_match('/[A-Z]{3,}/', $firstname) && preg_match('/[A-Z]{3,}/', $lastname)) {
                $custom_logger('error', "[SPAM][all caps name] $ip, $email, $firstname $lastname");
                wp_send_json_success(['message' => "Jméno nebo příjmení nemůže být psáno velkými písmeny."]);
                wp_die();
            }

            // Names with long gibberish
            if (
                (preg_match('/[a-zA-Z]{6,}/', $firstname) && !preg_match('/^[A-Z][a-z]+$/', $firstname)) ||
                (preg_match('/[a-zA-Z]{6,}/', $lastname) && !preg_match('/^[A-Z][a-z]+$/', $lastname))
            ) {
                $custom_logger('error', "[SPAM][gibberish name] $ip, $email, $firstname $lastname");
                wp_send_json_success(['message' => "Zadání obsahuje neplatné znaky."]);
                wp_die();
            }

            //END NEW CHECK

            // Step 1: Conditionally validate `firmy-svj` based on `zaka`
            $zaka = trim($formData['zaka']);
            $firmy_sv = trim($formData['firmy-sv']);
            
            // Step 1: Generate a new ID for the submission
            $exDetails = $wpdb->get_results("SELECT max(id) as maxId FROM contactformincrement", OBJECT);
            $exDetail = $exDetails[0];
            $maxId = $exDetail->maxId;
            $newid = $maxId + 1;
            $newmaxId = number_format($maxId, 0, ",", " ");
            // Insert new ID into the database
            $inserted = $wpdb->insert("contactformincrement", ["id" => $newid], ["%d"]);

            // Step 2: Check if the posted data is not empty
            if (empty($posted_data)) {
                return false; // If no data, return false.
            }
            if (strlen($posted_data["telefon"]) > 20) {
                $custom_logger('error', 'Phone too long (>20 chars).');
                wp_send_json_error(['message' => 'Telefon je příliš dlouhý. Kontaktujte nás na info@energosolar.cz.']);
                wp_die();
                // return "Je tam chyba. Kontaktujte nás na <a href='mailto:info@energosolar.cz'>info@energosolar.cz</a>";
            }
            $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();
            // $clockorder = isset($posted_data["clockorder"]) ? $posted_data["clockorder"] : '';
            $clockorder = $posted_data["clockorder"];
            if (!empty($clockorder)) {
                global $wpdb;

                // Try insert
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT IGNORE INTO ehubformincrement (ehubNumber) VALUES (%s)",
                        $clockorder
                    )
                );

                if ($result === 0) {
                    $custom_logger('error', "ehubNumber duplicate: {$clockorder} (generating new)");
                    // Insert failed (duplicate) → generate a new one
                    if (function_exists('get_random_ehub_number')) {
                        $clockorder = get_random_ehub_number();

                        $wpdb->insert(
                            'ehubformincrement',
                            ['ehubNumber' => $clockorder],
                            ['%s']
                        );
                    }
                }
            }

            $lead_ok = $wpdb->insert('wpih_leads', array(
                'form_id' => $maxId,
                'order_type' => $posted_data["zaka"],
                'name' => $posted_data["jmeno"],
                'surname' => $posted_data["prijmeni"],
                'firmy-sv' => $posted_data["firmy-svj"],
                'phone' => $posted_data["telefon"],
                'email' => $posted_data["email"],
                'region' => $posted_data["Kraj"],
                'district' => $posted_data["Okres"],
                'form_type' => 'footer',
                'notes' => $posted_data["message"],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'ehubNumber' => $clockorder,
                'Current_Page' => $referer
                
            ));

            if ($lead_ok === false) {
                $custom_logger('error', 'Failed to insert lead (wpih_leads): ' . $wpdb->last_error);
            }

            if ($posted_data) {
                // Step 3: Extract form data from $posted_data
                $subject = isset($posted_data["your-message"]) ? $posted_data["your-message"] : '';
                $today_date = dateToCzech("now", "j. F  Y");
                // Step 4: Process the 'zakapro' field to determine the subject
                // $zaka = isset($posted_data["zaka"]) ? $posted_data["zaka"] : '';
                // $zakapro = isset($zaka[0]) ? $zaka[0] : '';
                $zakapro = isset($posted_data["zaka"]) ? trim($posted_data["zaka"]) : '';
                $sub_zakapro = '';
                switch ($zakapro) {
                    case 'VIP elektrárna/FVE s umělou inteligencí':
                        $sub_zakapro = 'FVE s AI';
                        break;
                    case 'Fotovoltaika pro spotřebitele/domácnost':
                        $sub_zakapro = 'FVE domácnosti';
                        break;
                    case 'Tepelná čerpadla':
                        $sub_zakapro = 'Tepelná čerpadla';
                        break;
                    case 'Fotovoltaika s čerpadlem/elektronabíječkou':
                        $sub_zakapro = 'FVE s tepelným čerpadlem/elektronabíječkou';
                        break;
                    case 'Firmy a průmysl':
                        $sub_zakapro = 'Firmy';
                        break;
                    case 'SVJ a bytové domy':
                        $sub_zakapro = 'SVJ';
                        break;
                    case 'Komerční bateriová úložiště':
                        $sub_zakapro = 'Komerční bateriová úložiště';
                        break;
                    case 'Ostatní/jiné':
                        $sub_zakapro = 'Ostatní';
                        break;
                    default:
                        $sub_zakapro = 'Neznámá';
                        break;
                }

                $company_name = $firstname . " " . $lastname;
                $company_name = $firstname . " " . $lastname;
                $phone = isset($posted_data["telefon"]) ? $posted_data["telefon"] : '';
                $email = isset($posted_data["email"]) ? $posted_data["email"] : '';
                $okres = isset($posted_data["Okres"]) ? $posted_data["Okres"] : '';
                $Kraj = isset($posted_data["Kraj"]) ? $posted_data["Kraj"] : '';
                $gclid = isset($posted_data["gclid"]) ? $posted_data["gclid"] : '';
                $fbclid = isset($posted_data["fbclid"]) ? $posted_data["fbclid"] : '';
                $sznclid = isset($posted_data["sznclid"]) ? $posted_data["sznclid"] : '';
                $utm_source = isset($posted_data["utm_source"]) ? $posted_data["utm_source"] : '';
                $utm_campaign = isset($posted_data["utm_campaign"]) ? $posted_data["utm_campaign"] : '';
                $financovani = isset($posted_data["financovani"]) ? $posted_data["financovani"] : '';
                $emailoptout = isset($posted_data["emailoptout"]) ? $posted_data["emailoptout"] : '';
                $message = str_replace(['"', "'"], "", $posted_data['message']);
                $message_hidden = $posted_data['message_hidden'];
                $message = trim($message);
                if ($financovani == 1) {
                    $financovani = "Ano";
                } else {
                    $financovani = "Ne";
                }
                if ($emailoptout == 1) {
                    $emailoptout = "Ano";
                } else {
                    $emailoptout = "Ne";
                }
                $formOrigin = isset($posted_data['form_origin']) ? $posted_data['form_origin'] : ''; 
                $topic = "Nová poptávka: {$sub_zakapro} - {$firstname} {$lastname} ({$newmaxId}) na Energosolar.cz";
                if (!empty($formOrigin)) {
                    $topic .= " - {$formOrigin}";
                }
                $topic .= " ({$today_date})";
                $mail_subject  = $topic;
                $firmy_sv = !empty($posted_data['firmy-svj']) ? $posted_data['firmy-svj'] : 'Nevyplněno';
                $messageText = !empty($posted_data['message']) ? $posted_data['message'] : 'Nevyplněno';
                $form_url = esc_url_raw($_SERVER['HTTP_REFERER']);
                
                $label_firmy_sv = ($zaka == 'SVJ a bytové domy') ? 'Název SVJ' : 'Název firmy';
                $firmy_sv_label_line = $label_firmy_sv ? "<strong>{$label_firmy_sv}:</strong> {$firmy_sv}<br>" : '';
                
                $mail_body = "
                <strong>Typ zakázky:</strong> $zaka <br>
                {$firmy_sv_label_line}
                <strong>Jméno a příjmení:</strong> {$firstname} {$lastname}<br>
                <strong>E-mail:</strong> {$email}<br>
                <strong>Telefon:</strong> {$phone}<br>
                <strong>Kraj:</strong> {$Kraj}<br>
                <strong>Okres:</strong> {$okres}<br><br>
                <strong>Mám zájem o financování:</strong> {$financovani}<br>
                <strong>Souhlasím s užitím svých údajů pro marketingové účely:</strong> {$emailoptout}<br><br>
                <strong>Vaše poznámka:</strong> {$messageText}<br><br>
                <strong>Id:</strong> {$clockorder}<br><br>
                <span>Tento e-mail byl odeslán pomocí kontaktního formuláře energosolar<wbr>.cz: </span> {$form_url}
                ";
                $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url();
                $parsed = wp_parse_url($referer);
                $domain = isset($parsed['host']) ? $parsed['host'] : parse_url(home_url(), PHP_URL_HOST);
                $domain = preg_replace('/^www\./', '', $domain); // remove www if exists
                $admin_email = 'sales@' . $domain;
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: Energosolar.cz <' . $admin_email . '>',
                    'Reply-To: ' . $admin_email,
                );
                $mail_sent = wp_mail($admin_email, $mail_subject, $mail_body, $headers);
                $mail_subject2 = "Kopie údajů z vaší poptávky (" . $newmaxId . ") na Energosolar.cz (" . $today_date . ")";

                $mail_body2 = "
                    Dobrý den, vaše zpráva nám byla doručena. Kopii vyplněných údajů z formuláře pro kontrolu naleznete níže:
                    <br><br>
                    <strong>Typ zakázky:</strong> $zaka <br>
                    {$firmy_sv_label_line}
                    <strong>Jméno a příjmení:</strong> {$firstname} {$lastname}<br>
                    <strong>E-mail:</strong> {$email}<br>
                    <strong>Telefon:</strong> {$phone}<br>
                    <strong>Kraj:</strong> {$Kraj}<br>
                    <strong>Okres:</strong> {$okres}<br><br>
                    <strong>Mám zájem o financování:</strong> {$financovani}<br>
                    <strong>Souhlasím s užitím svých údajů pro marketingové účely:</strong> {$emailoptout}<br><br>
                    <strong>Vaše poznámka:</strong> {$messageText}
                    <br><br>
                    Buďte prosím dostupní na telefonu nebo emailu. Brzy vás budeme kontaktovat.
                    S pozdravem,<br><br>
                    Jana Šimáňová<br>
                    Zákaznický servis<br><br>
                    Telefon: +420 723 949 552<br>
                    Email: jana.simanova@energosolar.cz<br>
                    https://www.energosolar.cz/
                    ";
                $headers2 = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: Energosolar.cz <' . $admin_email . '>',
                    'Reply-To: ' . $admin_email,
                );
                $mail_sent2 = wp_mail($email, $mail_subject2, $mail_body2, $headers2);
                if (!$mail_sent)  { $custom_logger('error', 'Admin mail (wp_mail) failed.'); }
                if (!$mail_sent2) { $custom_logger('error', 'User copy mail (wp_mail) failed.'); }
                if ($mail_sent && $mail_sent2) {
                    $contactSource = "79";  // Default
                    if ($gclid) {
                        $contactSource = "261";
                    } elseif ($fbclid) {
                        $contactSource = "237";
                    } elseif ($sznclid) {
                        $contactSource = "260";
                    } elseif ($utm_source == "sklik") {
                        $contactSource = "264";
                    }
                    // Step 6: Format the message content
                    if (empty($message_hidden)) {
                        if (strpos($clockorder, "yandex") == '') {
                            $msg = $posted_data["message"];
                            $msg = sanitize_text_field($msg);
                            $msg_full = $msg . "<br> Financovani: " . $financovani . "<br> Emailoptout: " . $emailoptout . "<br> Zakázka pro: " . $zakapro;
                            $msg_full = "<br> From: " . $firstname .' '. $lastname . "<br><br> Právě dorazila nová objednávka/zpráva od zákazníka: " . $zakapro . "<br><br> Jméno: " . $company_name . "<br> E-mail: " . $email . "<br> Telefon: " . $phone . "<br> Kraj: " . $Kraj . "<br> Okres: " . $okres . "<br> Mám zájem o financování: " . $financovani . "<br> Souhlasím s využitím svých údajů pro marketingové účely: " . $emailoptout . "<br><br> Zpráva: " . $msg . "<br> Id: " . $clockorder . "<br><br> Tento formulář byl odeslán z Energosolar.cz: " . $_SERVER['HTTP_REFERER'];
                            $curl = curl_init();
                            $ch = curl_init();
                            // Set the URL
                            curl_setopt($ch, CURLOPT_URL, "https://app.raynet.cz/api/v2/lead");
                            // Indicate it's a POST request
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                            // Attach JSON data
                            $data = json_encode([
                            'topic' => $topic,
                            'firstName' => $firstname,
                            'lastName' => $lastname,
                            'companyName' => $company_name,
                            'owner' => 27,
                            'priority' => "DEFAULT",
                            'leadPhase' => 117,
                            'contactSource' => $contactSource,
                            'notice' => $msg_full,
                            'contactInfo' => [
                            'email' => $email,
                            'email2' => null,
                            'tel1' => $phone,
                            'tel1Type' => null,
                            'tel2' => null,
                            'tel2Type' => null,
                            'fax' => null,
                            'www' => null,
                            'otherContact' => null
                        ],
                        'address' => [
                            'city' => $okres,
                            'countryName' => "Česká republika",
                            'countryCode' => "CZ",
                            'province' => $Kraj
                        ]
                    ]);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                            // Set headers
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "X-Instance-Name: energosolarcrm",
                            "Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=",
                            'Content-Type: application/json',
                        ]);
                            // Set options
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                            // Execute the request
                            $response = curl_exec($ch);
                            if ($response === false) {
                                $custom_logger('error', 'cURL error (lead): ' . curl_error($ch));
                            }
                            if ($emailoptout == "Ano") {
                                curl_setopt_array(
                                    $curl,
                                    array(
                                        CURLOPT_URL => 'https://app.raynet.cz/api/v2/gdpr',
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'PUT',
                                        CURLOPT_POSTFIELDS => '{
                                            "lead": ' . $data->data->id . ',
                                            "gdprTemplate": 2,
                                            "validFrom": "' . date("Y-m-d") . '",
                                            "validTill": "' . date('Y-m-d', strtotime('+2 years', strtotime(date("Y-m-d")))) . '"
                                        }',
                                        CURLOPT_HTTPHEADER => array(
                                            'Authorization: Basic bWljaGFsLmtyY21hcmRvbWFpbkBnb29nbGVtYWlsLmNvbTpjcm0tYWZiNzMxNTY0MTAxNGM3ZGJiODc4NzU4NTRjZmQzMDE=',
                                            'X-Instance-Name: energosolarcrm',
                                            'Content-Type: text/plain'
                                        ),
                                    )
                                );
                                $response = curl_exec($curl);
                            }
                            curl_close($curl);
                        }
                    }
                }
            }
            $custom_logger('success', 'Custom form submitted successfully.');
            wp_send_json_success(['message' => 'Formulář byl úspěšně odeslán.']);
            exit;
        }
        add_action('wp_ajax_process_custom_contact_form_submission', 'process_custom_contact_form_submission');
        add_action('wp_ajax_nopriv_process_custom_contact_form_submission', 'process_custom_contact_form_submission');
function enqueue_custom_form_script() {
    wp_enqueue_script(
        'custom-form-cf7-trigger',
        'https://www.energosolar.cz/wp-content/themes/energosolar/assets/js/custom-form-cf7-trigger.js', // Adjust the path
        [],
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_form_script');


function custom_excerpt_more($more) {
    global $post;
    // Change 'Read More' to your custom text
    return '<div><a class="read-more" href="' . get_permalink($post->ID) . '">Read Full Article</a></div>';
}
add_filter('excerpt_more', 'custom_excerpt_more');
function check_1file_upload_session_callback() {
	
	
    $email = sanitize_email($_POST['email']);
    $reason = sanitize_text_field($_POST['reason']) ?? 'N/A';
    $mail_subject = "Rating email Working";
    $mail_body = "<html>
    <head>
    <title>Email Title</title>
    </head>
    <body>
    <h1 style='color: #333;'>Dobrý den,</h1>
    <p style='font-size: 16px;'>ještě je třeba potvrdit vaši emailovou adresu. Potvrzení provedete kliknutím na následující odkaz: </p>
    <a href=\"https://energosolar.ecomailapp.cz/public/subscribe/2/2bb287d15897fe2f9d89c882af9a3a8b\" style=\"background-color: #198754;padding:10px 20px;border-radius: 20px;color:#fff;text-decoration: none;font-size:14px;line-height:20px;width:fit-content !important;max-width:fit-content !important;height:20px;margin:30px auto 0 auto !important;display:block;\">Přihlásit se k odběru zde</a>
    </body>
    </html>";
    $headers = [
     'Content-Type: text/html; charset=UTF-8',
     'From: Energosolar.cz <sales@energosolar.cz>',
     'Reply-To: sales@energosolar.cz',
 ];
 $mail_sent1 = wp_mail('dotazy@energosolar.cz', $mail_subject, $mail_body, $headers);
 $mail_sent3 = wp_mail($email, $mail_subject, $mail_body, $headers);
 if ($mail_sent1 && $mail_sent3) {
    echo json_encode(['status' => 'success', 'message' => $rs]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send emails']);
}
    wp_die(); // Important for AJAX requests
}
add_action("wp_ajax_check_1file_upload_session", "check_1file_upload_session_callback");
add_action("wp_ajax_nopriv_check_1file_upload_session", "check_1file_upload_session_callback");

function check_3file_upload_session_callback() {
	$post_id = $_POST['postID'];
    $action =$_POST['actions'];
    $sign = $_POST['sign'];
    global $wpdb;
    $table_name = 'wpih_like_dislike_count';
    $rowss = $wpdb->get_results("SELECT * FROM $table_name WHERE post_id = $post_id",OBJECT);
    if ($rowss) {
      $rows = (array)$rowss[0];
      if ($action === 'like') {
        $new_like_count = ($sign == 'plus')? $rows['likes'] + 1 : $rows['likes'] - 1;
        $wpdb->update(
            $table_name,
            array('likes' => $new_like_count),
            array('post_id' => $post_id)
        );
        return $new_like_count; 
    } elseif ($action === 'dislike') {
       $new_dislike_count = ($sign == 'plus')? $rows['dislikes'] + 1 : $rows['dislikes'] - 1;
       $wpdb->update(
        $table_name,
        array('dislikes' => $new_dislike_count),
        array('post_id' => $post_id)
    );
       return $new_dislike_count;
   }
} else {
    $initial_like = ($action === 'like') ? 1 : 0;
    $initial_dislike = ($action === 'dislike') ? 1 : 0;
    $h =$wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'likes' => $initial_like,
            'dislikes' => $initial_dislike,
        ),
        array('%d', '%d', '%d')
    );
    return ($action === 'like') ? $h : 0; 
}
}
add_action("wp_ajax_check_3file_upload_session", "check_3file_upload_session_callback");
add_action("wp_ajax_nopriv_check_3file_upload_session", "check_3file_upload_session_callback");

function check_2file_upload_session_callback() {
    global $wpdb;
    $data = [];
    $post_id = $_POST['postID'];
    // Table name (make sure it's the correct table prefix)
    $table_name = 'wpih_like_dislike_count';
    // Using $wpdb->get_results to fetch the result as an array of objects
    $rowss = $wpdb->get_results("SELECT * FROM $table_name WHERE post_id = $post_id",OBJECT);
    // Check if any row is returned
    if (!empty($rowss)) {
      $rows = (array)$rowss[0];
        $data['dislikes'] = $rows['dislikes']; // Access object properties
        $data['likes'] = $rows['likes'];
    } else {
        // If no record found, set default values
        $data['dislikes'] = 0;
        $data['likes'] = 0;
    }
    // Return the response in JSON format
    echo json_encode([$data]);
    wp_die(); // Important for AJAX requests
}
add_action("wp_ajax_check_2file_upload_session", "check_2file_upload_session_callback");
add_action("wp_ajax_nopriv_check_2file_upload_session", "check_2file_upload_session_callback");

function remove_entry_content_class() {
if (is_page('test-page')) { // Replace with your actual page slug
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var entryContent = document.querySelector(".entry-content");
        if (entryContent) {
            entryContent.classList.remove("entry-content");
        }
        });
        </script>';
    }
    
}
add_action('wp_footer', 'remove_entry_content_class');
add_filter('wpcf7_spam', '__return_false');

    /* Igor's functions */
function energosolar_theme_slug_setup() {
    load_child_theme_textdomain( 'twentytwentyone', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'energosolar_theme_slug_setup' );
add_filter( 'gettext', 'custom_translate_text', 20, 3 );
function custom_translate_text( $translated_text, $text, $domain ) {
    if ( $text === 'Undefined value was submitted through this field' ) {
        $translated_text = 'Prostřednictvím tohoto pole byla odeslána nedefinovaná hodnota';
    }
    return $translated_text;
}
function energostar_continue_reading_link_excerpt( $more ) {
    $permalink = get_permalink();
    $read_more = '<div><a href="' . esc_url( $permalink ) . '">Prečíst celý článek <i class="fa fa-chevron-right" style="position: relative; top: 2px; left: 3px;" aria-hidden="true"></i></a></div>';
    return $read_more;
}
add_action( 'after_setup_theme', function() {
    remove_filter( 'excerpt_more', 'twenty_twenty_one_continue_reading_link_excerpt' );
    add_filter( 'excerpt_more', 'energostar_continue_reading_link_excerpt' );
});

function refresh_recaptcha_on_popup() {
    ?>
    <script type="text/javascript">
    (function($) {
        function refreshRecaptchaToken() {
            if (typeof grecaptcha !== 'undefined' && typeof wpcf7_recaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    grecaptcha.execute(wpcf7_recaptcha.sitekey, {
                        action: 'homepage'
                    }).then(function(token) {
                        $('.wpcf7-form').each(function() {
                            var $form = $(this);
                            var $recaptchaResponse = $form.find(
                                '[name="_wpcf7_recaptcha_response"]');
                            if ($recaptchaResponse.length > 0) {
                                $recaptchaResponse.val(token);
                                console.log('ReCAPTCHA token updated for form:', $form);
                            } else {
                                console.log('ReCAPTCHA response input not found in form:',
                                    $form);
                            }
                        });
                    });
                });
            } else {
                console.log('wpcf7_recaptcha is undefined.');
            }
        }
        // PopupMaker refresh
        $(document).on('pumAfterOpen', function() {
            console.log('Popup opened.');

            if (typeof wpcf7 !== 'undefined' && $.isFunction(wpcf7.initForm)) {
                $('.wpcf7-form').each(function() {
                    wpcf7.initForm($(this));
                    console.log('CF7 form re-initialized:', $(this));
                });
            }
            refreshRecaptchaToken();
        });
    })(jQuery);
    </script>
    <?php
}
add_action('wp_footer', 'refresh_recaptcha_on_popup');

function disable_honeypot_autocomplete() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var honeypot = document.querySelector('input[name="honeypot-626"]');
        if (honeypot) {
            honeypot.setAttribute('autocomplete', 'off');
            honeypot.style.display = 'none'; // optional to further hide it
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        var honeypot_footer = document.querySelector('input[name="honeypot-369"]');
        if (honeypot_footer) {
            honeypot_footer.setAttribute('autocomplete', 'off');
            honeypot_footer.style.display = 'none'; // optional to further hide it
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'disable_honeypot_autocomplete');

function verify_recaptcha_token($token)
{
    error_log('reCAPTCHA verification result: ' . print_r($data, true));


    $secret = RECAPTCHA_SECRET_KEY;
    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret' => $secret,
            'response' => $token
        ]
    ]);

    if (is_wp_error($response)) {
        error_log('reCAPTCHA verification error: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('Google reCAPTCHA response: ' . print_r($body, true));
    return isset($body['success']) && $body['success'] === true && $body['score'] >= 0.3;
}

function add_hotjar_tracking_code() {
    ?>
    <!-- Hotjar Tracking Code for Energosolar.cz -->
    <script>
        (function(h,o,t,j,a,r){
            h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
            h._hjSettings={hjid:6510268,hjsv:6};
            a=o.getElementsByTagName('head')[0];
            r=o.createElement('script');r.async=1;
            r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
            a.appendChild(r);
        })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
    </script>
    <?php
}
add_action('wp_head', 'add_hotjar_tracking_code'); // puts it inside <head>

// Function For Logs the Error.
function log_form_submission($args = []) {
    global $wpdb;

    $defaults = [
        'form_type'      => 'cf7',
        'form_id'        => '',
        'page_url'       => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
        'user_ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'submitted_data' => '',
        'status'         => 'success',
        'error_message'  => '',
    ];
    $data = wp_parse_args($args, $defaults);

    $wpdb->insert(
        'form_submission_logs',
        [
            'form_type'      => $data['form_type'],
            'form_id'        => $data['form_id'],
            'page_url'       => $data['page_url'],
            'user_ip'        => $data['user_ip'],
            'user_agent'     => $data['user_agent'],
            'submitted_data' => maybe_serialize($data['submitted_data']),
            'status'         => $data['status'],
            'error_message'  => $data['error_message'],
        ],
        ['%s','%s','%s','%s','%s','%s','%s','%s']
    );
}
// Block spammy or fake emails early in CF7 validation
add_filter('wpcf7_validate_email*', 'block_spammy_emails', 20, 2);
add_filter('wpcf7_validate_email', 'block_spammy_emails', 20, 2);

function block_spammy_emails($result, $tag) {
    $email = isset($_POST[$tag->name]) ? sanitize_email($_POST[$tag->name]) : '';

    // List of blocked domains
    $spam_domains = ['mailinator.com', 'example.com', 'test.com', 'fake.com', 'centrum.cz'];
    foreach ($spam_domains as $bad) {
        if (stripos($email, $bad) !== false) {
            $result->invalidate($tag, __('E-mailová adresa není povolena.', 'contact-form-7'));
            return $result;
        }
    }

    // Block obvious fake patterns
    $spam_patterns = [
        '/^test/i',        // starts with "test"
        '/1234/i',         // contains 1234
        '/fake/i',         // contains fake
        '/mailinator/i',   // disposable email
    ];
    foreach ($spam_patterns as $pattern) {
        if (preg_match($pattern, $email)) {
            $result->invalidate($tag, __('E-mailová adresa není povolena.', 'contact-form-7'));
            return $result;
        }
    }

    return $result;
}

// ✅ Validate first name (jmeno) and last name (prijmeni)
add_filter('wpcf7_validate_text*', 'cf7_validate_name_fields', 20, 2);
add_filter('wpcf7_validate_text',  'cf7_validate_name_fields', 20, 2);

function cf7_validate_name_fields($result, $tag) {
    $field_name = $tag->name;

    // Validate only these specific fields
    if (in_array($field_name, ['jmeno', 'prijmenu'])) {
        $value = sanitize_text_field($_POST[$field_name] ?? '');

        // 1️⃣ Check for numbers
        if (preg_match('/[0-9]/', $value)) {
            $result->invalidate($tag, __('Jméno nesmí obsahovat čísla.', 'contact-form-7'));
            return $result;
        }

        // 2️⃣ Check for too short names
        if (strlen($value) < 2) {
            $result->invalidate($tag, __('Jméno nebo příjmení je příliš krátké.', 'contact-form-7'));
            return $result;
        }

        // 3️⃣ Check for long gibberish (e.g. “Lakhvirtdsfdsf”, “Qwrtplkjhgf”)
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{6,}/i', $value)) {
            $result->invalidate($tag, __('Zadání obsahuje neplatné znaky.', 'contact-form-7'));
            return $result;
        }

        // 4️⃣ Check for all caps (e.g. “LAKHVIR”)
        if (preg_match('/^[A-ZČĎÉĚÍŇÓŘŠŤÚŮÝŽ]{3,}$/u', $value)) {
            $result->invalidate($tag, __('Jméno nebo příjmení nemůže být psáno velkými písmeny.', 'contact-form-7'));
            return $result;
        }

        // 5️⃣ Optional — check for repeated characters (e.g. “aaaabbbb”)
        if (preg_match('/(.)\1{3,}/', $value)) {
            $result->invalidate($tag, __('Zadání obsahuje opakující se znaky.', 'contact-form-7'));
            return $result;
        }
    }

    return $result;
}

