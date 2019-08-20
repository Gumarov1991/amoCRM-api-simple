<?php

require_once __DIR__ . '/functions.php';

if (isset($_COOKIE['subdomain'])) {
    $subdomain = $_COOKIE['subdomain'];
    $leads = getLeadsThisMonth($subdomain)['_embedded']['items'];
}
if (isset($_POST['function'])) {
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
            print_r("Контакт №{$contactName} успешно создан");
        } else {
            nt_r('Ошибка обработки формы');
        }
        $leads = getLeadsThisMonth($subdomain)['_embedded']['items'];
    }
}
?>

<?php if (!isset($leads)): ?>
<h3>Автоизация</h3>
<form action="" method="POST" >
    <input type="hidden" name = "function" value = "auth" required>
    <div>
        <label>
            Ваш логин
            <input type="text" name="login" value="" required>
        </label>
    </div>
    <div>
        <label>
            Subdomain
            <input type="text" name="subdomain" value="" required>
        </label>
    </div>
    <div>
        <label>
            ApiKey
            <input type="text" name="apiKey" value="" required>
        </label>
    </div>
    <input type="submit" value="Войти">
</form>
<?php else: ?>
<hr>
<p>Ваш логин: <?=isset($_COOKIE['login']) ? $_COOKIE['login'] : $_POST['login']?> </p>
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

<?php if (isset($leads)): ?>
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