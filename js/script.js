function vllContactsPerPage() {
    var el = document.getElementById('per_page');
    return el && el.value ? el.value : '10';
}

function vllContactsStartRow() {
    var el = document.getElementById('start_row');
    return el && el.value ? el.value : '1';
}

function cancel_scheduled() {

    var start_row = document.getElementById('start_row').value;
    var per_page = document.getElementById('per_page').value;

    var sms_ids = get_items('scheduled');
    if (sms_ids.length > 0) {
        if (confirm("Are you sure you want to cancel selected messages")) {
            var phpurl = "cancel_scheduled.php?sms_ids=" + sms_ids;
            var xmlhttp;
            if (window.XMLHttpRequest) {
                xmlhttp = new XMLHttpRequest();
            } else {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    get_scheduled(start_row, per_page);
                }
            }
            xmlhttp.open("GET", phpurl, false);
            xmlhttp.send();
        }
    }
}

function keygen(){
    var phpurl = "keygen.php";
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('secret_key').innerHTML=xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}

function download_history() {
    var from_date = document.getElementById('from_date').value;
    var to_date = document.getElementById('to_date').value;

    document.location.href = "download_history.php?from_date=" + from_date + "&to_date=" + to_date;
}


function delete_sms_order(order_id) {
    if (confirm("Are you sure you want to delete this order?")) {
        var phpurl = "delete_sms_order.php?order_id=" + order_id;
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                document.location.reload();
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();
    }
}


function save_new_password() {
    var new_password = document.getElementById('new_password').value;
    var confirm_password = document.getElementById('confirm_password').value;

    var errors = 0;
    document.getElementById('form_errors').innerHTML = "";

    if (new_password.length < 6) {
        document.getElementById('form_errors').innerHTML += "<div> - Password length must be atleast 6 characters</div>";
        errors += 1;
    }
    if (confirm_password != new_password) {
        document.getElementById('form_errors').innerHTML += "<div> - Password did not match</div>";
        errors += 1;
    }

    if (errors == 0) {
        document.getElementById('password_form').submit();
    }
}



function save_user() {
    var client_name = document.getElementById('client_name').value;
    var phone_number = document.getElementById('phone_number').value;
    var email = document.getElementById('email').value;
    var username = document.getElementById('username').value;
    var new_password = document.getElementById('new_password').value;
    var confirm_password = document.getElementById('confirm_password').value;

    var errors = 0;
    document.getElementById('form_errors').innerHTML = "";

    if (client_name.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter client name</div>";
        errors += 1;
    }

    if (phone_number.length != 12) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter a valid phone number</div>";
        errors += 1;
    }


    if (email.length != 0) {
        var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
        if (!email.match(mailformat)) {
            document.getElementById('form_errors').innerHTML += "<div> - You must enter a valid email address</div>";
            errors += 1;
        }
    }

    if (username.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter username</div>";
        errors += 1;
    }

    var available = check_username(username);

    if (available == false) {
        document.getElementById('form_errors').innerHTML += "<div> - Username already taken</div>";
        errors += 1;
    }

    if (new_password.length < 6) {
        document.getElementById('form_errors').innerHTML += "<div> - Password length must be atleast 6 characters</div>";
        errors += 1;
    }

    if (confirm_password != new_password) {
        document.getElementById('form_errors').innerHTML += "<div> - Password did not match</div>";
        errors += 1;
    }

    if (errors == 0) {
        document.getElementById('client_form').submit();
    } else {
        document.getElementById('form_errors').scrollIntoView();
    }
}

function check_username(username) {
    var available = false;

    var phpurl = "check_username.php?username=" + username;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (xmlhttp.responseText == "Available") {
                available = true;
            } else {
                available = false;
            }
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();

    return available;
}



function send_custom() {
    var phpurl = "send_custom.php";
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (xmlhttp.responseText == "Sent") {
                document.location.href = "preview.php?r=Sent";
            } else {
                document.location.href = "preview.php?r=Failed: Insufficient balance";
            }
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}



function get_preview(start_row, per_page) {
    var from_date = document.getElementById('from_date').value;
    var to_date = document.getElementById('to_date').value;

    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_preview.php?start_row=" + start_row + "&per_page=" + per_page + "&from_date=" + from_date + "&to_date=" + to_date + "&keyword=" + keyword;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('page-content').innerHTML = xmlhttp.responseText;
            document.getElementById('per_page').value = per_page;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}

function preview() {

    var data = encodeURIComponent(document.getElementById('data').value);

    var phpurl = "preview_modal.php?data=" + data + "&sender_id" + sender_id + "&phone_column=" + phone_column + "&message=" + message + "&schedule=" + schedule + "&start_date=" + start_date + "&send_hour=" + send_hour + "&send_minute=" + send_minute + "&end_date=" + end_date;

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('preview_modal_content').innerHTML = xmlhttp.responseText;
            document.getElementById('preview').click();
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}

function insert_placeholder() {
    var placeholder = document.getElementById('placeholder').value;
    var message = document.getElementById('message').value;

    document.getElementById('message').value = message + placeholder;
    document.getElementById('placeholder').selectedIndex = 0;
}


function upload_file() {
    var file_name = document.getElementById('file_name').value;

    var errors = 0;
    document.getElementById('file_form_errors').innerHTML = "";

    if (file_name.length == 0) {
        document.getElementById('file_form_errors').innerHTML += "<div> - You must select a file</div>";
        errors += 1;
    }

    if (errors == 0) {
        document.getElementById('composer').submit();
    }
}

function download_contacts() {
    document.getElementById('import_contacts').click();
}

function import_contacts_file() {
    var file_name = document.getElementById('contacts_file').value;
    document.getElementById('import_form_errors').innerHTML = "";
    if (file_name.length == 0) {
        document.getElementById('import_form_errors').innerHTML = "<div> - You must select a CSV/XLSX file</div>";
        return false;
    }
    document.getElementById('import_contacts_form').submit();
}



function customize_sms() {
    var phone_column = document.getElementById('phone_column').value;
    var sender_id = document.getElementById('sender_id').value;
    var message = document.getElementById('message').value;
    var schedule = document.getElementById('schedule').value;
    var start_date = document.getElementById('start_date').value;
    var end_date = document.getElementById('end_date').value;
    var send_hour = document.getElementById('send_hour').value;
    var send_minute = document.getElementById('send_minute').value;

    var antispam = document.getElementById('antispam').checked;

    var errors = 0;
    document.getElementById('file_form_errors').innerHTML = "";

    if (phone_column.length == 0) {
        document.getElementById('file_form_errors').innerHTML += "<div> - You must select column containing phone numbers</div>";
        errors += 1;
    }

    if (sender_id.length == 0) {
        document.getElementById('file_form_errors').innerHTML += "<div> - You must select Sender ID</div>";
        errors += 1;
    }

    if (message.length == 0) {
        document.getElementById('file_form_errors').innerHTML += "<div> - You must enter message</div>";
        errors += 1;
    }

    if (antispam == false) {
        document.getElementById('file_form_errors').innerHTML += "<div> - You must accept our terms of service to continue</div>";
        errors += 1;
    }
    
    if (errors == 0) {
        document.getElementById('composer').submit();
    }
}

function place_order() {
    var quantity = +document.getElementById('quantity').value;
    if (quantity > 0) {
        document.location.href = "place_order.php?quantity=" + quantity;
    } else {
        document.getElementById('qty_error').innerHTML = "You must enter quantity";
        return false;
    }
}

function get_total_cost() {
    var quantity = +document.getElementById('quantity').value;
    var total_cost = 0;
    if (quantity >= 0) {
        var price_array = document.getElementById('price_array').value.split(",");

        for (var i = 0; i < price_array.length; i++) {
            var tier = price_array[i].split('@');

            var pricing_range = tier[0].split('-');

            var min_sms = pricing_range[0];
            var max_sms = pricing_range[1];
            var price = tier[1];
            if (quantity >= min_sms && quantity <= max_sms) {
                total_cost = quantity * price;
            } else {
                if (max_sms == 0 && quantity >= min_sms) {
                    total_cost = quantity * price;
                }
            }
        }

    } else {
        document.getElementById('quantity').value = "";
    }

    var formatter = new Intl.NumberFormat();
    document.getElementById('total_cost').innerHTML = "TSH " + formatter.format(total_cost);
}


function change_password() {
    var current_password = document.getElementById('current_password').value;
    var new_password = document.getElementById('new_password').value;
    var confirm_password = document.getElementById('confirm_password').value;

    var errors = 0;
    document.getElementById('form_errors').innerHTML = "";

    if (current_password.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter your current password</div>";
        errors += 1;
    }


    if (new_password.length < 6) {
        document.getElementById('form_errors').innerHTML += "<div> - Password length must be atleast 6 characters</div>";
        errors += 1;
    }

    if (confirm_password != new_password) {
        document.getElementById('form_errors').innerHTML += "<div> - Password did not match</div>";
        errors += 1;
    }
    if (errors == 0) {
        document.getElementById('password_form').submit();
    }
}

function delete_senders(sender_id) {
    var start_row = document.getElementById('start_row').value;
    var per_page = document.getElementById('per_page').value;

    var phpurl = "delete_senders.php?sender_id=" + sender_id;

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            get_senders(start_row, per_page);
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();

}


function update_template(start_row, per_page, template_id) {
    var keyword = document.getElementById('keyword').value;
    var title = document.getElementById('edit_title').value;
    var message = document.getElementById('edit_message').value;

    var errors = 0;
    document.getElementById('edit_form_errors').innerHTML = "";

    if (title.length == 0) {
        document.getElementById('edit_form_errors').innerHTML += "<div> - You must enter a title</div>";
        errors += 1;
    }

    if (message.length == 0) {
        document.getElementById('edit_form_errors').innerHTML += "<div> - You must enter message</div>";
        errors += 1;
    }

    if (errors == 0) {
        var phpurl = "update_template.php?title=" + title + "&message=" + message + "&template_id=" + template_id;

        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                var start_row = document.getElementById('start_row').value;
                var per_page = document.getElementById('per_page').value;
                get_templates(start_row, per_page);
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();

        document.getElementById('edit_template').click();
    }
}


function edit_template(template_id) {
    var phpurl = "edit_template_modal.php?template_id=" + template_id;

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('edit_template_modal_content').innerHTML = xmlhttp.responseText;
            document.getElementById('edit_template').click();
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}


function delete_templates(template_id) {
    var start_row = document.getElementById('start_row').value;
    var per_page = document.getElementById('per_page').value;

    var phpurl = "delete_templates.php?template_id=" + template_id;

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            get_templates(start_row, per_page);
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();

}


function save_template(start_row, per_page) {
    var keyword = document.getElementById('keyword').value;
    var title = document.getElementById('title').value;
    var message = document.getElementById('message').value;

    var errors = 0;
    document.getElementById('form_errors').innerHTML = "";

    if (title.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter a title</div>";
        errors += 1;
    }

    if (message.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter message</div>";
        errors += 1;
    }

    if (errors == 0) {
        var phpurl = "save_template.php?title=" + title + "&message=" + message;

        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                var start_row = document.getElementById('start_row').value;
                var per_page = document.getElementById('per_page').value;
                get_templates(1, per_page);
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();

        document.getElementById('create_template').click();
    }
}


function save_sender(start_row, per_page) {
    var keyword = document.getElementById('keyword').value;
    var sender_id = document.getElementById('sender_id').value;
    var message = document.getElementById('message').value;

    var errors = 0;
    document.getElementById('form_errors').innerHTML = "";

    if (sender_id.length == 0 || sender_id.length > 11) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter a valid Sender ID</div>";
        errors += 1;
    }

    if (message.length == 0) {
        document.getElementById('form_errors').innerHTML += "<div> - You must enter sample message</div>";
        errors += 1;
    }

    if (errors == 0) {
        var phpurl = "save_sender.php?sender_id=" + sender_id + "&message=" + message;

        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                var start_row = document.getElementById('start_row').value;
                var per_page = document.getElementById('per_page').value;
                get_senders(1, per_page);
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();

        document.getElementById('create_sender').click();
    }
}



function update_contact(start_row, per_page, contact_id) {
    var group_id = document.getElementById('group_id').value;
    var keyword = document.getElementById('keyword').value;
    var region = document.getElementById('edit_phone_region').value;
    var phone_number = normalize_contact_phone_client(document.getElementById('edit_phone_number').value, region);
    var contact_name = document.getElementById('edit_contact_name').value;
    var email = document.getElementById('edit_email').value;

    var errors = 0;
    document.getElementById('edit_form_errors').innerHTML = "";

    if (!is_valid_contact_phone_client(phone_number, region)) {
        document.getElementById('edit_form_errors').innerHTML += "<div> - Enter a valid phone for the selected country.</div>";
        errors += 1;
    }

    if (contact_name.length == 0) {
        document.getElementById('edit_form_errors').innerHTML += "<div> - You must enter contact name</div>";
        errors += 1;
    }

    if (email.length != 0) {
        var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
        if (!email.match(mailformat)) {
            document.getElementById('edit_form_errors').innerHTML += "<div> - You must enter a valid email address</div>";
            errors += 1;
        }
    }

    if (errors == 0) {
        var phpurl = "update_contact.php?contact_id=" + contact_id + "&group_id=" + group_id + "&region=" + encodeURIComponent(region) + "&phone_number=" + encodeURIComponent(phone_number) + "&contact_name=" + encodeURIComponent(contact_name) + "&email=" + encodeURIComponent(email);

        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                if (xmlhttp.responseText == "Duplicate") {
                    document.getElementById('edit_form_errors').innerHTML += "<div> - Contact with this phone number already created</div>";
                } else if (xmlhttp.responseText.trim() == "Invalid") {
                    document.getElementById('edit_form_errors').innerHTML += "<div> - Invalid phone number for selected country</div>";
                } else {
                    var start_row = vllContactsStartRow();
                    var per_page = vllContactsPerPage();
                    get_contacts(start_row, per_page);
                    document.getElementById('edit_contact').click();
                }
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();


    }
}


function edit_contact(contact_id) {
    var phpurl = "edit_contact_modal.php?contact_id=" + contact_id;

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('edit_contact_modal_content').innerHTML = xmlhttp.responseText;
            document.getElementById('edit_contact').click();
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}

function bulk_delete_contacts() {
    var contacts = get_items('contacts').split(",");
    var contact_ids = "";

    for (var i = 0; i < (contacts.length - 1); i++) {
        contact_ids += (contacts[i].split("_")[1]) + ",";
    }

    if (contact_ids.length == 0) {
        alert("Please select at least one contact to delete.");
        return false;
    }

    var start_row = vllContactsStartRow();
    var per_page = vllContactsPerPage();

    var group_id = document.getElementById('group_id').value;

    var phpurl = "bulk_delete_contacts.php?contact_ids=" + encodeURIComponent(contact_ids) + "&group_id=" + encodeURIComponent(group_id);

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            get_contacts(start_row, per_page);
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}




function delete_contacts(contact_id, group_id) {
    if (!confirm("Are you sure you want to delete this contact?")) {
        return false;
    }
    var start_row = vllContactsStartRow();
    var per_page = vllContactsPerPage();

    var phpurl = "delete_contacts.php?contact_id=" + encodeURIComponent(contact_id) + "&group_id=" + encodeURIComponent(group_id);

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            get_contacts(start_row, per_page);
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();

}


function get_groups_list() {
    var phpurl = "get_groups_list.php";
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('group_id').innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();

}


function save_group(start_row, per_page) {
    var group_name = document.getElementById('group_name').value;

    var errors = 0;
    document.getElementById('group_form_errors').innerHTML = "";


    if (group_name.length == 0) {
        document.getElementById('group_form_errors').innerHTML += "<div> - You must enter group name</div>";
        errors += 1;
    }

    if (errors == 0) {
        var phpurl = "save_group.php?group_name=" + encodeURIComponent(group_name);
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                if (xmlhttp.responseText == "Duplicate") {
                    document.getElementById('group_form_errors').innerHTML = "<div> - Group already exists</div>";
                    return false;
                }
                if (xmlhttp.responseText == "Error") {
                    document.getElementById('group_form_errors').innerHTML = "<div> - Failed to save group, please retry</div>";
                    return false;
                }
                get_groups_list();
                get_contacts(1, vllContactsPerPage());
                document.getElementById('group_name').value = "";
                document.getElementById('create_group').click();
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();
    }
}


function normalize_contact_phone_client(phoneDigits, region) {
    var r = (region || "TZ").toUpperCase();
    phoneDigits = String(phoneDigits).replace(/\D/g, "");
    if (phoneDigits.indexOf("00") === 0) {
        phoneDigits = phoneDigits.substring(2);
    }
    if (r === "OTHER") {
        return phoneDigits;
    }
    var cc = (r === "KE") ? "254" : (r === "UG") ? "256" : "255";
    if (phoneDigits.length >= 12 && phoneDigits.indexOf(cc) === 0) {
        return phoneDigits;
    }
    if (phoneDigits.length == 10 && phoneDigits.charAt(0) == "0") {
        return cc + phoneDigits.substring(1);
    }
    if (phoneDigits.length == 9) {
        return cc + phoneDigits;
    }
    return phoneDigits;
}

function is_valid_contact_phone_client(digits, region) {
    var r = (region || "TZ").toUpperCase();
    if (r === "OTHER") {
        return digits.length >= 10 && digits.length <= 15;
    }
    var cc = (r === "KE") ? "254" : (r === "UG") ? "256" : "255";
    return digits.length === 12 && digits.indexOf(cc) === 0;
}

function save_contact(start_row, per_page) {
    var group_id = document.getElementById('group_id').value;
    var keyword = document.getElementById('keyword').value;
    var region = document.getElementById('contact_phone_region').value;
    var phone_number = document.getElementById('phone_number').value;
    var contact_name = document.getElementById('contact_name').value;
    var email = document.getElementById('email').value;

    var errors = 0;
    document.getElementById('contact_form_errors').innerHTML = "";
    phone_number = normalize_contact_phone_client(phone_number, region);

    if (!is_valid_contact_phone_client(phone_number, region)) {
        document.getElementById('contact_form_errors').innerHTML += "<div> - Enter a valid phone for the selected country (national 0… / 9 digits, or full international).</div>";
        errors += 1;
    }

    if (contact_name.length == 0) {
        document.getElementById('contact_form_errors').innerHTML += "<div> - You must enter contact name</div>";
        errors += 1;
    }

    if (email.length != 0) {
        var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
        if (!email.match(mailformat)) {
            document.getElementById('contact_form_errors').innerHTML += "<div> - You must enter a valid email address</div>";
            errors += 1;
        }
    }
    if (errors == 0) {
        var phpurl = "save_contact.php?group_id=" + encodeURIComponent(group_id) + "&region=" + encodeURIComponent(region) + "&phone_number=" + encodeURIComponent(phone_number) + "&contact_name=" + encodeURIComponent(contact_name) + "&email=" + encodeURIComponent(email);

        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                if (xmlhttp.responseText == "Duplicate") {
                    document.getElementById('contact_form_errors').innerHTML = "<div> - Contact already exists</div>";
                    return false;
                }
                if (xmlhttp.responseText == "Invalid") {
                    document.getElementById('contact_form_errors').innerHTML = "<div> - Invalid phone number format</div>";
                    return false;
                }
                if (xmlhttp.responseText == "Error") {
                    document.getElementById('contact_form_errors').innerHTML = "<div> - Failed to save contact, please retry</div>";
                    return false;
                }
                get_contacts(1, per_page);
                document.getElementById('phone_number').value = "";
                document.getElementById('contact_name').value = "";
                document.getElementById('email').value = "";
                document.getElementById('create_contact').click();
            }
        }
        xmlhttp.open("GET", phpurl, false);
        xmlhttp.send();
    }
}

function send_sms() {
    var contacts = document.getElementById('contacts').value;
    var groups = document.getElementById('groups').value;
    var sender_id = document.getElementById('sender_id').value;
    var message = document.getElementById('message').value;
    var schedule = document.getElementById('schedule').value;
    var start_date = document.getElementById('start_date').value;
    var end_date = document.getElementById('end_date').value;
    var send_hour = document.getElementById('send_hour').value;
    var send_minute = document.getElementById('send_minute').value;
    var antispam = document.getElementById('antispam').checked;


    var total_recipients = +document.getElementById('total_recipients').innerHTML;
    var errors = 0;
    document.getElementById('compose_form_errors').innerHTML = "";
    if (total_recipients <= 0) {
        document.getElementById('compose_form_errors').innerHTML += "<div> - You must enter recipients</div>";
        errors += 1;
    }

    if (message.length == 0) {
        document.getElementById('compose_form_errors').innerHTML += "<div> - You must enter a message</div>";
        errors += 1;
    }

    if (sender_id.length == 0) {
        document.getElementById('compose_form_errors').innerHTML += "<div> - You must select Sender ID</div>";
        errors += 1;
    }

    if (antispam == false) {
        document.getElementById('compose_form_errors').innerHTML += "<div> - You must accept our terms of service to continue</div>";
        errors += 1;
    }


    if (errors == 0) {
        document.getElementById('composer').submit();
    } else {
        return false;
    }

}

function count_message() {
    var message = document.getElementById('message').value;
    var message_length = message.length;
    var sms_count = Math.ceil(message_length / 160);
    var sms_length = message_length - (160 * sms_count) + 160;
    document.getElementById('message_length').innerHTML = message_length + "/" + sms_count * 160;
    document.getElementById('sms_count').innerHTML = sms_count;
}


function parse_message(message) {
    document.getElementById('message').value = parseHTML(message);

}

function parseHTML(html) {
    return html;
}


function remove_recipient_contact(this_contact, phone_number) {
    this_contact.parentNode.parentNode.removeChild(this_contact.parentNode);
    var this_number = phone_number + ",";

    document.getElementById('contacts').value = document.getElementById('contacts').value.replace(this_number, "");
    get_total_recipients()
}

function remove_recipient_group(this_group, group_id) {
    this_group.parentNode.parentNode.removeChild(this_group.parentNode);
    var this_group_id = group_id + ",";

    document.getElementById('groups').value = document.getElementById('groups').value.replace(this_group_id, "");
    get_total_recipients()
}



function toggle_all(status, table_name) {
    var fields = document.getElementById(table_name).getElementsByTagName("input");
    for (var i = 0, max = fields.length; i < max; i++) {
        if (fields[i].type === 'checkbox' && fields[i].id.length !== 0) {

            var id = fields[i].id.split("_");
            if (id[0] == "item") {
                if (status == true) {
                    fields[i].checked = true;
                } else {
                    fields[i].checked = false;
                }
            }
        }
    }
}

function get_items(table_name) {
    var items = "";
    var root = document.getElementById(table_name);
    if (!root) {
        return "";
    }
    var fields = root.getElementsByTagName("input");
    for (var i = 0, max = fields.length; i < max; i++) {
        if (fields[i].type === 'checkbox' && fields[i].id.length !== 0) {
            var id = fields[i].id;
            var item_id = fields[i].id.split("_");
            if (item_id[0] == "item") {
                var ticked = fields[i].checked;
                if (ticked) {
                    items = items + id + ",";
                }
            }
        }
    }
    return items;
}

function insert_templates() {
    var templates = get_items('modal_templates').split(",");

    var template_ids = "";
    for (var i = 0; i < (templates.length - 1); i++) {
        if (templates[i] === "") {
            continue;
        }
        var parts = templates[i].split("_");
        if (parts.length < 2) {
            continue;
        }
        template_ids += parts.slice(1).join("_") + ",";
    }

    if (template_ids === "") {
        alert("Please select one or more templates.");
        return;
    }

    var phpurl = "get_template_text.php?items=" + encodeURIComponent(template_ids);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            alert("Could not insert template. Please try again.");
            return;
        }
        var message = document.getElementById('message').value + xmlhttp.responseText;
        document.getElementById('message').value = message;
        count_message();
        var cb = document.getElementById('insert_templates');
        if (cb) {
            cb.click();
        }
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();
}

function insert_groups() {
    var groups = get_items('modal_groups').split(",");

    var group_ids = "";

    for (var i = 0; i < (groups.length - 1); i++) {
        if (groups[i] === "") {
            continue;
        }
        var parts = groups[i].split("_");
        if (parts.length < 2) {
            continue;
        }
        group_ids += parts.slice(1).join("_") + ",";
    }

    if (group_ids === "") {
        alert("Please select one or more groups.");
        return;
    }

    var phpurl = "get_group_names.php?items=" + encodeURIComponent(group_ids);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            alert("Could not insert groups. Please try again.");
            return;
        }
        document.getElementById('groups').value += group_ids;
        var recipient_list = xmlhttp.responseText + document.getElementById('recipient_list').innerHTML;
        document.getElementById('recipient_list').innerHTML = recipient_list;
        get_total_recipients();
        var cb = document.getElementById('insert_groups');
        if (cb) {
            cb.click();
        }
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();
}


function insert_contacts() {
    var contacts = get_items('modal_contacts').split(",");

    var phone_numbers = "";

    for (var i = 0; i < (contacts.length - 1); i++) {
        if (contacts[i] === "") {
            continue;
        }
        var parts = contacts[i].split("_");
        if (parts.length < 2) {
            continue;
        }
        phone_numbers += parts.slice(1).join("_") + ",";
    }

    if (phone_numbers === "" || phone_numbers === ",") {
        alert("Please tick one or more contacts to insert.");
        return;
    }

    var phpurl = "get_contact_names.php?items=" + encodeURIComponent(phone_numbers);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            alert("Could not load contact names. Check your connection and try again.");
            return;
        }
        document.getElementById('contacts').value += phone_numbers;
        var recipient_list = xmlhttp.responseText + document.getElementById('recipient_list').innerHTML;
        document.getElementById('recipient_list').innerHTML = recipient_list;
        var cb = document.getElementById('insert_contacts');
        if (cb) {
            cb.click();
        }

        get_total_recipients();
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();
}

function get_contacts_modal(start_row, per_page) {

    var phpurl = "get_contacts_modal.php?start_row=" + encodeURIComponent(start_row) + "&per_page=" + encodeURIComponent(per_page);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            document.getElementById('contacts_modal_content').innerHTML = "<p style='padding:12px;'>Could not load contacts.</p>";
            return;
        }
        document.getElementById('contacts_modal_content').innerHTML = xmlhttp.responseText;
        var sel = document.getElementById('contacts_modal_per_page');
        if (sel) {
            sel.value = String(per_page);
        }
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();

}


function get_groups_modal(start_row, per_page) {
    var phpurl = "get_groups_modal.php?start_row=" + encodeURIComponent(start_row) + "&per_page=" + encodeURIComponent(per_page);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            document.getElementById('groups_modal_content').innerHTML = "<p style='padding:12px;'>Could not load groups.</p>";
            return;
        }
        document.getElementById('groups_modal_content').innerHTML = xmlhttp.responseText;
        var sel = document.getElementById('groups_modal_per_page');
        if (sel) {
            sel.value = String(per_page);
        }
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();

}



function get_templates_modal(start_row, per_page) {
    var phpurl = "get_templates_modal.php?start_row=" + encodeURIComponent(start_row) + "&per_page=" + encodeURIComponent(per_page);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            document.getElementById('templates_modal_content').innerHTML = "<p style='padding:12px;'>Could not load templates.</p>";
            return;
        }
        document.getElementById('templates_modal_content').innerHTML = xmlhttp.responseText;
        var sel = document.getElementById('templates_modal_per_page');
        if (sel) {
            sel.value = String(per_page);
        }
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();

}

function parse_recipient() {
    var recipient = document.getElementById('recipient').value.replace(/\s+/g, "");
    if (recipient.length < 10 || recipient.length > 15) {
        return;
    }

    var phpurl = "get_contact_names.php?items=" + encodeURIComponent(recipient + ",");
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState !== 4) {
            return;
        }
        if (xmlhttp.status !== 200) {
            alert("Could not add recipient. Please try again.");
            return;
        }
        var recipient_list = xmlhttp.responseText + document.getElementById('recipient_list').innerHTML;
        document.getElementById('recipient_list').innerHTML = recipient_list;
        document.getElementById('recipient').value = '';
        var contacts = document.getElementById('contacts').value + recipient + ",";
        document.getElementById('contacts').value = contacts;
        get_total_recipients();
    };
    xmlhttp.open("GET", phpurl, true);
    xmlhttp.send();
}

function get_total_recipients() {
    var total_contacts = (document.getElementById('contacts').value.split(",").length) - 1;

    var total_group_contacts = 0;

    var groups = document.getElementById('groups').value;

    var phpurl = "get_total_group_contacts.php?groups=" + groups;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            total_group_contacts = + xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();


    var total_recipients = total_contacts + total_group_contacts;
    document.getElementById('total_recipients').innerHTML = total_recipients;
}

function get_history(start_row, per_page) {
    var from_date = document.getElementById('from_date').value;
    var to_date = document.getElementById('to_date').value;
    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_history.php?start_row=" + start_row + "&per_page=" + per_page + "&from_date=" + from_date + "&to_date=" + to_date + "&keyword=" + keyword;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('page-content').innerHTML = xmlhttp.responseText;
            document.getElementById('per_page').value = per_page;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}

function get_contacts(start_row, per_page) {

    var group_id = document.getElementById('group_id').value;
    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_contacts.php?start_row=" + start_row + "&per_page=" + per_page + "&group_id=" + encodeURIComponent(group_id) + "&keyword=" + encodeURIComponent(keyword);

    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var pc = document.getElementById('page-content');
            if (pc) {
                pc.innerHTML = xmlhttp.responseText;
            }
            var pp = document.getElementById('per_page');
            if (pp) {
                pp.value = String(per_page);
            }
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}


function get_templates(start_row, per_page) {
    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_templates.php?start_row=" + start_row + "&per_page=" + per_page + "&keyword=" + keyword;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('page-content').innerHTML = xmlhttp.responseText;
            document.getElementById('per_page').value = per_page;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}


function get_senders(start_row, per_page) {
    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_senders.php?start_row=" + start_row + "&per_page=" + per_page + "&keyword=" + keyword;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('page-content').innerHTML = xmlhttp.responseText;
            document.getElementById('per_page').value = per_page;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}


function get_scheduled(start_row, per_page) {
    var from_date = document.getElementById('from_date').value;
    var to_date = document.getElementById('to_date').value;

    var keyword = document.getElementById('keyword').value;

    var phpurl = "get_scheduled.php?start_row=" + start_row + "&per_page=" + per_page + "&from_date=" + from_date + "&to_date=" + to_date + "&keyword=" + keyword;
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('page-content').innerHTML = xmlhttp.responseText;
            document.getElementById('per_page').value = per_page;
        }
    }
    xmlhttp.open("GET", phpurl, false);
    xmlhttp.send();
}


// Restricts input for the given textbox to the given inputFilter.
function setInputFilter(textbox, inputFilter) {
    ["input", "keydown", "keyup", "mousedown", "mouseup", "select", "contextmenu", "drop"].forEach(function (event) {
        textbox.addEventListener(event, function () {
            if (inputFilter(this.value)) {
                this.oldValue = this.value;
                this.oldSelectionStart = this.selectionStart;
                this.oldSelectionEnd = this.selectionEnd;
            } else if (this.hasOwnProperty("oldValue")) {
                this.value = this.oldValue;
                this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
            } else {
                this.value = "";
            }
        });
    });
}
