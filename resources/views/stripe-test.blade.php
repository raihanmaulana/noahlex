<!DOCTYPE html>
<html>
<head>
    <title>Stripe Subscription Test</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>

<h2>Test Stripe Subscription</h2>

<!-- Tambahkan input email -->
<input type="email" id="email" value="test@example.com">
<button id="checkoutBtn" style="padding:10px 20px; font-size:16px;">
    Subscribe Core Plan
</button>

<script>
    var stripe = Stripe("{{ config('stripe.key') }}");

    document.getElementById("checkoutBtn").addEventListener("click", function () {
        fetch("/create-checkout-session", { // ✅ sesuai route
            method: "POST",
            headers: { 
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                email: document.getElementById('email').value
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.id) {
                stripe.redirectToCheckout({ sessionId: data.id });
            } else {
                alert("Gagal membuat checkout session");
            }
        })
        .catch(err => console.error(err));
    });
</script>

</body>
</html>
