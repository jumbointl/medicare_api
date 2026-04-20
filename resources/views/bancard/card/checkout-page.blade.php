<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago con tarjeta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 24px;
            background: #f7f7f9;
        }
        .box {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #ddd;
        }
        #iframe-container {
            min-height: 650px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
<div class="box">
    <h1>Pagar cita #{{ $payment->id_appointment }}</h1>
    <p><strong>Payment ID:</strong> {{ $payment->id }}</p>
    <p><strong>Monto:</strong> {{ number_format((float) $payment->amount, 2, '.', ',') }} {{ $payment->currency_code }}</p>

    <div id="iframe-container"></div>
</div>

<script src="{{ config('bancard_card.checkout_script_url') }}"></script>
<script>
    window.onload = function () {
        const processId = @json($processId);

        const styles = {
            "input-background-color": "#ffffff",
            "input-text-color": "#333333",
            "input-border-color": "#cccccc",
            "input-placeholder-color": "#999999",
            "button-background-color": "#6f42c1",
            "button-text-color": "#ffffff",
            "button-border-color": "#6f42c1"
        };

        if (!window.Bancard || !window.Bancard.Checkout) {
            console.error('No se cargó Bancard Checkout');
            return;
        }

        Bancard.Checkout.createForm('iframe-container', processId, styles);
    };
</script>
</body>
</html>