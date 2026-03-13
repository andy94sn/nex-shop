<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmare Comandă {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 620px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a1a2e; color: #fff; padding: 32px 40px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 6px 0 0; font-size: 14px; opacity: .8; }
        .body { padding: 32px 40px; }
        .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin: 24px 0 12px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; font-size: 12px; color: #888; padding: 0 0 8px; border-bottom: 1px solid #eee; }
        table.items td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: top; }
        table.items td:last-child { text-align: right; white-space: nowrap; }
        .totals { margin-top: 16px; }
        .totals-row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
        .totals-row.total { font-weight: 700; font-size: 16px; border-top: 2px solid #eee; margin-top: 8px; padding-top: 12px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; }
        .info-item label { font-size: 12px; color: #888; display: block; margin-bottom: 2px; }
        .info-item span { font-size: 14px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 12px; font-weight: 600; background: #e8f5e9; color: #2e7d32; }
        .footer { background: #f9f9f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
        .footer a { color: #aaa; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Mulțumim pentru comanda dvs.!</h1>
        <p>Comanda <strong>{{ $order->order_number }}</strong> a fost înregistrată cu succes.</p>
    </div>
    <div class="body">

        {{-- Contact & Delivery --}}
        <div class="section-title">Date de contact &amp; livrare</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Nume</label>
                <span>{{ $order->contact_name }}</span>
            </div>
            <div class="info-item">
                <label>Email</label>
                <span>{{ $order->contact_email }}</span>
            </div>
            <div class="info-item">
                <label>Telefon</label>
                <span>{{ $order->contact_phone }}</span>
            </div>
            @if($order->shippingRegion)
            <div class="info-item">
                <label>Regiune livrare</label>
                <span>{{ $order->shippingRegion->getTranslation('name', 'ro') }}</span>
            </div>
            @endif
            @if($order->shipping_address)
            <div class="info-item" style="grid-column: span 2;">
                <label>Adresă livrare</label>
                <span>{{ $order->shipping_address }}</span>
            </div>
            @endif
            <div class="info-item">
                <label>Metodă de plată</label>
                <span>{{ ucfirst($order->payment_method) }}</span>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span class="badge">{{ ucfirst($order->status) }}</span>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="section-title">Produse comandate</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Produs</th>
                    <th style="text-align:center">Cant.</th>
                    <th style="text-align:right">Preț</th>
                    <th style="text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->title }}<br>
                        <small style="color:#999">Art: {{ $item->article }}</small>
                    </td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ number_format($item->price, 2) }} lei</td>
                    <td style="text-align:right">{{ number_format($item->total, 2) }} lei</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>{{ number_format($order->subtotal, 2) }} lei</span>
            </div>
            @if($order->discount > 0)
            <div class="totals-row" style="color:#c0392b">
                <span>Reducere cupon</span>
                <span>− {{ number_format($order->discount, 2) }} lei</span>
            </div>
            @endif
            @if($order->shipping_cost > 0)
            <div class="totals-row">
                <span>Livrare</span>
                <span>+ {{ number_format($order->shipping_cost, 2) }} lei</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>Total de achitat</span>
                <span>{{ number_format($order->total, 2) }} lei</span>
            </div>
        </div>

        @if($order->notes)
        <div class="section-title">Note</div>
        <p style="font-size:14px; background:#fafafa; padding:12px; border-radius:6px; border:1px solid #eee;">
            {{ $order->notes }}
        </p>
        @endif

    </div>
    <div class="footer">
        &copy; {{ date('Y') }} NexDistribution. Toate drepturile rezervate.<br>
        <a href="#">nexdistribution.md</a>
    </div>
</div>
</body>
</html>
