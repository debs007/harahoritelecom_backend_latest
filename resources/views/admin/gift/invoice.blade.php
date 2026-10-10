<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gift Invoice {{ $claim->claim_number }}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans; font-size:13px; color:#1f2937; line-height:1.5; }
  .page { padding:40px 48px; }
  .header { display:table; width:100%; border-bottom:3px solid #7c3aed; padding-bottom:20px; margin-bottom:28px; }
  .l { display:table-cell; width:50%; vertical-align:top; }
  .r { display:table-cell; width:50%; vertical-align:top; text-align:right; }
  .brand { font-size:26px; font-weight:900; color:#7c3aed; }
  .muted { font-size:11px; color:#6b7280; margin-top:2px; }
  .label { font-size:22px; font-weight:800; }
  .num { color:#7c3aed; font-weight:700; margin-top:4px; }
  .row { display:table; width:100%; margin-bottom:28px; }
  .box { display:table-cell; width:50%; vertical-align:top; padding-right:20px; }
  .title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#7c3aed; border-bottom:1px solid #e5e7eb; padding-bottom:4px; margin-bottom:8px; }
  .name { font-weight:700; }
  table.items { width:100%; border-collapse:collapse; margin-bottom:24px; }
  table.items th { background:#7c3aed; color:#fff; font-size:11px; text-transform:uppercase; padding:10px 12px; text-align:left; }
  table.items td { padding:11px 12px; border-bottom:1px solid #f3f4f6; }
  .total { background:#7c3aed; color:#fff; padding:12px; font-size:15px; font-weight:900; text-align:right; border-radius:6px; }
  .note { text-align:center; background:#f5f3ff; border:1px solid #e9d5ff; border-radius:10px; padding:16px; margin:24px 0 20px; font-size:12px; color:#6b7280; }
  .footer { border-top:2px solid #e5e7eb; padding-top:16px; text-align:center; font-size:11px; color:#9ca3af; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div class="l">
      <div class="brand">Harahori Telecom</div>
      <div class="muted">support@harahoritelecom.in &nbsp;|&nbsp; www.harahoritelecom.in</div>
    </div>
    <div class="r">
      <div class="label">GIFT INVOICE</div>
      <div class="num"># {{ $claim->claim_number }}</div>
      <div class="muted">Date: {{ ($claim->confirmed_at ?? $claim->created_at)->format('d F Y') }}</div>
    </div>
  </div>

  <div class="row">
    <div class="box">
      <div class="title">Issued To</div>
      <div class="name">{{ $claim->user?->name }}</div>
      <div>{{ $claim->email }}</div>
    </div>
    <div class="box">
      <div class="title">Deliver To</div>
      <div class="name">{{ $claim->ship_name }}</div>
      <div>{{ $claim->ship_phone }}</div>
      <div class="muted">{{ $claim->full_address }}</div>
    </div>
  </div>

  <table class="items">
    <thead><tr><th style="width:70%">Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Value</th></tr></thead>
    <tbody>
      <tr>
        <td><strong>{{ $claim->gift_name }}</strong><br><span class="muted">Complimentary welcome gift</span></td>
        <td style="text-align:center">1</td>
        <td style="text-align:right">&#8377;0.00</td>
      </tr>
    </tbody>
  </table>

  <div class="total">TOTAL PAYABLE &nbsp; &#8377;0.00</div>

  <div class="note">This is a complimentary gift. No payment is due.</div>

  <div class="footer">
    <strong>Harahori Telecom</strong> &nbsp;·&nbsp; This is a computer-generated document and does not require a signature.<br>
    Generated on {{ now()->format('d M Y, h:i A') }}
  </div>
</div>
</body>
</html>
