jQuery(document).ready(function ($) {

    const $checkoutForm = $('#cdl-checkout-form');
    const $paymentButton = $('#cdl-checkout-payment-button');

    $checkoutForm.hide();

    function handleCheckoutForm() {

        $checkoutForm.hide();

        window.openCheckout = function () {
            const transaction = createTransactionObject();

            signTransaction(transaction).done(function (signature) {
                initializeCheckout(signature, transaction);
            }).fail(function () {
                console.error('Transaction signing failed.');
                $checkoutForm.show();
            });
        };

        openCheckout();
        return false;
    }

    function createTransactionObject() {
        return {
            totalAmount: cdlCheckoutData.totalAmount,
            customerEmail: cdlCheckoutData.customerEmail,
            customerPhone: cdlCheckoutData.customerPhone,
            sessionId: generateUniqueSessionId(15),
            products: cdlCheckoutData.products
        };
    }

    function signTransaction(transaction) {
        return $.ajax({
            url: cdlCheckoutData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'sign_transaction',
                nonce: cdlCheckoutData.signTransactionNonce,
                transaction: transaction
            }
        });
    }

    function initializeCheckout(signature, transaction) {
        const config = {
            publicKey: cdlCheckoutData.publicKey,
            signature: signature,
            transaction: transaction,
            isLive: cdlCheckoutData.isLive,
            onSuccess: function () {
                window.location.href = cdlCheckoutData.returnUrl;
            },
            onClose: function () {
                $checkoutForm.show();
            },
            onPopup: function (response) {
                $("body").unblock();
                saveCheckoutTransactionId(response.checkoutTransactionId);
            }
        };

        const connect = new Connect(config);
        connect.setup();
        connect.open();
    }

    function saveCheckoutTransactionId(transactionId) {
        $.ajax({
            url: cdlCheckoutData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'save_transaction_id',
                order_id: cdlCheckoutData.orderId,
                nonce: cdlCheckoutData.saveCheckoutTransactionIdNonce,
                checkoutTransactionId: transactionId
            }
        });
    }

    function generateUniqueSessionId(length) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        return Array.from({ length }, () => characters.charAt(Math.floor(Math.random() * characters.length))).join('');
    }

    handleCheckoutForm();

    $paymentButton.on('click', handleCheckoutForm);

});