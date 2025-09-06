<?php
// Получаем параметры из GET
$method = $_GET['method'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$orderId = $_GET['orderId'] ?? '';
$email = $_GET['email'] ?? '';
$returnUrl = urldecode($_GET['returnUrl'] ?? '');

// Твои API-ключи (храни в .env или БД!)
$qiwiApiKey = 'https://qiwi.com/'; // Из QIWI Касса
$yoomoneyShopId = 'YOUR_YOOMONEY_SHOP_ID'; // Из ЮKassa
$yoomoneySecret = 'YOUR_YOOMONEY_SECRET';
$paypalUsername = 'YOUR_PAYPAL_USERNAME';
$paypalPassword = 'YOUR_PAYPAL_PASSWORD';
$paypalSignature = 'YOUR_PAYPAL_SIGNATURE';

if ($amount <= 0) {
    die('Ошибка: неверная сумма');
}

switch ($method) {
    case 'qiwi':
        // Создание счета в QIWI (Payout API или Касса)
        $url = 'https://api.qiwi.com/partner/bill/v1/bills'; // Для Касса
        $data = json_encode([
            'amount' => ['value' => $amount, 'currency' => 'RUB'],
            'comment' => 'Оплата заказа #' . $orderId,
            'expirationDateTime' => date('c', strtotime('+1 hour')), // Срок 1 час
            'customer' => ['phone' => '79123456789'], // Опционально
            'successUrl' => $returnUrl . '&status=success',
            'customFields' => ['orderId' => $orderId, 'email' => $email]
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $qiwiApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $bill = json_decode($response, true);
            header('Location: ' . $bill['payUrl']); // Редирект на форму QIWI
            exit;
        } else {
            die('Ошибка QIWI: ' . $response);
        }
        break;
        
    case 'yoomoney':
        // Создание платежа в ЮKassa
        $url = 'https://api.yookassa.ru/v3/payments';
        $data = json_encode([
            'amount' => ['value' => $amount, 'currency' => 'RUB'],
            'confirmation' => ['type' => 'redirect', 'return_url' => $returnUrl . '&status=success'],
            'capture' => true,
            'description' => 'Оплата заказа #' . $orderId,
            'receipt' => ['customer' => ['email' => $email]],
            'metadata' => ['orderId' => $orderId]
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Idempotence-Key: ' . uniqid(), // Для идемпотентности
            'Authorization: Basic ' . base64_encode($yoomoneyShopId . ':' . $yoomoneySecret)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $payment = json_decode($response, true);
            header('Location: ' . $payment['confirmation']['confirmation_url']);
            exit;
        } else {
            die('Ошибка YooMoney: ' . $response);
        }
        break;
        
    case 'paypal':
        // PayPal REST API (v2 Orders)
        $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders'; // sandbox для теста, prod: api-m.paypal.com
        $data = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => ['currency_code' => 'USD', 'value' => $amount / 80], // Конверт в USD (примерно 1 USD ~80 RUB)
                'description' => 'Оплата заказа #' . $orderId
            ]],
            'application_context' => [
                'return_url' => $returnUrl . '&status=success',
                'cancel_url' => $returnUrl . '&status=cancel'
            ]
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($paypalUsername . ':' . $paypalPassword), // Или используй Access Token
            'PayPal-Partner-Attribution-Id' => $paypalSignature // Если нужно
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 201) {
            $order = json_decode($response, true);
            $approveUrl = $order['links'][1]['href']; // approve href
            header('Location: ' . $approveUrl);
            exit;
        } else {
            die('Ошибка PayPal: ' . $response);
        }
        break;
        
    default:
        die('Неизвестный метод оплаты');
}

// После успешного платежа (webhook или callback): отправь email с товарами
// Пример: mail($email, 'Ваши товары', 'Код Minecraft: ABC123\nRoblox: 1000 Robux\n...');
?>