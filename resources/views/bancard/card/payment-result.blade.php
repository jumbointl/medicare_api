<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><h1 class="{{ $status === 'success' ? 'ok' : 'fail' }}">
    Resultado de pago: {{ $status }}
    </h1></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; }
        .box { max-width: 680px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 24px; }
        h1 { margin-top: 0; }
        .ok { color: #0a7a2f; }
        .fail { color: #b42318; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <div class="box">
        <h1 class="{{ $status === 'success' ? 'ok' : 'fail' }}">
            Resultado de pago: {{ $status }}
        </h1>
        @if(!empty($paymentId))
            <p><strong>Payment ID:</strong> {{ $paymentId }}</p>
        @endif
        @if(!empty($message))
            <p><strong>Mensaje:</strong> {{ $message }}</p>
        @endif

        @if(!empty($processId))
            <p><strong>Process ID:</strong> {{ $processId }}</p>
        @endif

        <p class="muted">Ya puedes volver a la app.</p>
    </div>
</body>
</html>