<?php
if ($_COOKIE['subdomain']) {
    $subdomain = $_COOKIE['subdomain'];
    $leads = getLeadsThisMonth($subdomain)['_embedded']['items'];
}
if ($_POST['function'] === 'auth') {
    $login = $_POST['login'];
    $apiKey = $_POST['apiKey'];
    $subdomain = $_POST['subdomain'];
    auth($login, $apiKey, $subdomain);
    $leads = getLeadsThisMonth($subdomain)['_embedded']['items'];
}
if ($_POST['function'] === 'bindLeadContact') {
    $subdomain = $_COOKIE['subdomain'];
    $leadName = $_POST['leadName'];
    $leadSale = $_POST['leadSale'];
    $contactName = $_POST['contactName'];
    if ($leadName && $leadSale && $contactName) {
        $idAddedContact = addContact($subdomain, $contactName)['_embedded']['items'][0]['id'];
        $idAddedLead = addLead($subdomain, $leadName, $leadSale)['_embedded']['items'][0]['id'];
        bindLeadContact($subdomain, $idAddedContact, $idAddedLead);
        print_r("Сделка №{$idAddedLead} и контакт №{$idAddedContact} успешно созданы и соединены");
    } elseif ($leadName && $leadSale) {
        $idAddedLead = addLead($subdomain, $leadName, $leadSale)['_embedded']['items'][0]['id'];
        print_r("Сделка №{$idAddedLead} успешно создана");
    } elseif ($contactName) {
        addContact($subdomain, $contactName)['_embedded']['items'][0]['id'];
        print_r("Контакт №{$contactName} успешно создана");
    } else {
        print_r('Ошибка обработки формы');
    }
    $leads = getLeadsThisMonth($subdomain)['_embedded']['items'];
}

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
        print_r('Авторизация успешна!');
        return true;
    }
    print_r('Ошибка авторизации');
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
?>

<?php if (empty($leads)): ?>
<h3>Автоизация</h3>
<form action="" method="POST" >
    <input type="hidden" name = "function" value = "auth" required>
    <div>
        <label>
            Ваш логин
            <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
        </label>
    </div>
    <div>
        <label>
            Subdomain
            <input type="text" name="subdomain" value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>" required>
        </label>
    </div>
    <div>
        <label>
            ApiKey
            <input type="text" name="apiKey" value="<?= htmlspecialchars($_POST['apiKey'] ?? '') ?>" required>
        </label>
    </div>
    <input type="submit" value="Войти">
</form>
<?php else: ?>
<hr>
<p>Ваш логин: <?=$_COOKIE['login'] ? $_COOKIE['login'] : $_POST['login']?> </p>
<hr>
<h2>Добавление контакта и сделки и их соединенеие</h2>
<p>Если заполнить данные только сделки или только контакта, будет создана только эта сущность.</p>
<p>Если заполнить все поля, будет создан контакт и сделка, и после соединены. </p>
<form action="" method="POST">
    <input type="hidden" name = "function" value="bindLeadContact" required>
    <div>
        <label>
            Имя сделки
            <input type="text" name="leadName" value="">
        </label>
    </div>
    <div>
        <label>
            Сумма сделки
            <input type="text" name="leadSale" value="">
        </label>
    </div>
    <div>
        <label>
            Имя контакта
            <input type="text" name="contactName" value="">
        </label>
    </div>
    <input type="submit" value="Выполнить">
</form>
<?php endif; ?>

<?php if ($leads): ?>
<hr>
<h2>Активные сделки за этот месяц</h2>
<table>
    <tr>
        <th>Id сделки</th>
        <th>Название сделки</th>
        <th>Бюджет</th>
	</tr>
    <?php foreach ($leads as $lead) : ?>
    <tr>
        <td><?=$lead['id']?></td>
        <td><?=$lead['name']?></td>
        <td><?=$lead['sale']?></td>
    <tr>
    <?php endforeach; ?>
<table>
<?php endif; ?>