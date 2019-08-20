<?php

function bindLeadContact($subdomain, $contactId, $leadId)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads');
    $leads['update'] = [
        [
            'id' => $leadId,
            'contacts_id' => $contactId
        ]
    ];
    return execCurl($link, $leads);
}

function addLead($subdomain, $nameLead, $saleLead)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads');
    $leads['add'] = [
        [
            'name' => $nameLead,
            'sale' => $saleLead
        ]
    ];
    return execCurl($link, $leads);
}

function addContact($subdomain, $nameContact)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/contacts');
    $contacts['add'] = [
        [
            'name' => $nameContact
        ]
    ];
    return execCurl($link, $contacts);
}

function getLeadsThisMonth($subdomain)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads?', 'filter/active');
    $date = new \DateTime('first day of this month');
    $firstDayOfThisMonth = $date->setTime(0, 0)->format('D, d M Y H:i:s');
    return execCurl($link, NULL, $firstDayOfThisMonth);
}

function auth($login, $apiKey, $subdomain)
{
    $user = [
        'USER_LOGIN' => $login,
        'USER_HASH' => $apiKey
    ];
    $method = '.amocrm.ru/private/api/auth.php?';
    $param = 'type=json';
    $link = genLink($subdomain, $method, $param);
    $out = execCurl($link, $user);
    $response = $out['response'];
    if (isset($response['auth'])) {
        setcookie('login', $login, time()+800);
        setcookie('subdomain', $subdomain, time()+800);
        return true;
    }
    return false;
}

function genLink($subdomain, $method, $param = '')
{
    return 'https://' . $subdomain . $method . $param;
}

function execCurl($link, $postFields, $httpHeader = '')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    if ($postFields) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    if ($httpHeader) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("IF-MODIFIED-SINCE: {$httpHeader}"));
    }
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return json_decode($out, true);
}
