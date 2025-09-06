let cart = [];
let cartCount = 0;

function addToCart(itemName, price) {
    cart.push({ name: itemName, price: price });
    cartCount++;
    document.getElementById('cart-count').textContent = cartCount;
    updateCartModal();
    alert(`${itemName} добавлен в корзину!`);
}

function updateCartModal() {
    const cartItems = document.getElementById('cart-items');
    const totalPrice = document.getElementById('total-price');
    const paymentSection = document.getElementById('payment-section');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    cartItems.innerHTML = '';
    let total = 0;

    cart.forEach((item, index) => {
        const div = document.createElement('div');
        div.innerHTML = `<p>${item.name} - ${item.price} руб.</p><button onclick="removeFromCart(${index})">Удалить</button>`;
        cartItems.appendChild(div);
        total += item.price;
    });

    totalPrice.textContent = `Итого: ${total} руб.`;
    
    // Показываем секцию платежа, если корзина не пуста
    if (total > 0) {
        paymentSection.style.display = 'block';
        checkoutBtn.style.display = 'none'; // Скрываем старую кнопку
        generatePaymentButtons(total);
    } else {
        paymentSection.style.display = 'none';
        checkoutBtn.style.display = 'block';
    }
}

function generatePaymentButtons(total) {
    const buttonsDiv = document.getElementById('payment-buttons');
    const method = document.getElementById('payment-method').value;
    buttonsDiv.innerHTML = `<button class="pay-btn" onclick="redirectToPayment('${method}', ${total})">Оплатить через ${method.toUpperCase()}</button>`;
}

function redirectToPayment(method, total) {
    // Генерируем уникальный ID заказа (timestamp + random)
    const orderId = Date.now() + Math.random().toString(36).substr(2, 9);
    
    // Редирект на backend с параметрами
    const backendUrl = `YOUR_BACKEND_URL/checkout.php?method=${method}&amount=${total}&orderId=${orderId}&email=user@example.com&returnUrl=${encodeURIComponent(window.location.href + '?success=1')}`;
    window.location.href = backendUrl;
}

function processPayment() {
    // Проверяем, выбран ли метод
    const method = document.getElementById('payment-method').value;
    if (!method) {
        alert('Выберите способ оплаты!');
        return;
    }
    const total = cart.reduce((sum, item) => sum + item.price, 0);
    if (total === 0) {
        alert('Корзина пуста!');
        return;
    }
    redirectToPayment(method, total);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    cartCount--;
    document.getElementById('cart-count').textContent = cartCount;
    updateCartModal();
}

function toggleCart() {
    const modal = document.getElementById('cart-modal');
    modal.style.display = modal.style.display === 'block' ? 'none' : 'block';
}

// Обработчик изменения метода оплаты
document.getElementById('payment-method').addEventListener('change', (e) => {
    const total = cart.reduce((sum, item) => sum + item.price, 0);
    generatePaymentButtons(total);
});

// Проверка возврата после оплаты (в URL ?success=1)
window.addEventListener('load', () => {
    if (window.location.search.includes('success=1')) {
        alert('Оплата успешна! Товары отправлены на email.');
        cart = []; // Очищаем корзину
        cartCount = 0;
        document.getElementById('cart-count').textContent = '0';
        updateCartModal();
    }
});

// ... (остальной код без изменений)