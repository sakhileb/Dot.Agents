<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Alert — Dot.Agents</title>
<style>
  body { margin: 0; padding: 0; background: #f9f9f7; font-family: 'Inter', Arial, sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(61,46,160,0.08); }
  .header { padding: 32px 40px; }
  .header.paused { background: #1e1660; }
  .header.decommissioned { background: #111827; }
  .header.error { background: #dc2626; }
  .header.drift_detected { background: #d97706; }
  .header.default { background: #3d2ea0; }
  .header h1 { color: #f5be1c; font-size: 20px; margin: 0; font-weight: 700; }
  .header p { color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 14px; }
  .body { padding: 40px; }
  .body p { color: #374151; line-height: 1.6; margin: 0 0 16px; }
  .alert-box { background: #f3f4f6; border-left: 4px solid #3d2ea0; border-radius: 0 8px 8px 0; padding: 16px 20px; margin: 24px 0; }
  .alert-box.error { border-color: #dc2626; }
  .alert-box.drift_detected { border-color: #d97706; }
  .btn { display: inline-block; background: #3d2ea0; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; margin-top: 24px; }
  .footer { background: #f9f9f7; padding: 24px 40px; text-align: center; }
  .footer p { color: #9ca3af; font-size: 12px; margin: 4px 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header {{ $alertType }}">
    <h1>⚡ Dot.Agents</h1>
    <p>
      @switch($alertType)
        @case('paused') Agent Deployment Paused @break
        @case('decommissioned') Agent Deployment Decommissioned @break
        @case('error') Agent Deployment Error @break
        @case('drift_detected') Agent Drift Detected @break
        @default Agent Deployment Alert @endswitch
    </p>
  </div>
  <div class="body">
    <p>An alert has been triggered for your agent deployment <strong>{{ $deploymentName }}</strong>.</p>

    <div class="alert-box {{ $alertType }}">
      <strong style="color:#111827;">{{ ucfirst(str_replace('_', ' ', $alertType)) }}</strong>
      <p style="margin:8px 0 0; color:#374151;">{{ $alertMessage }}</p>
    </div>

    @if(!empty($context))
    <p style="font-size:13px; color:#6b7280;">
      Additional context: <code style="background:#f3f4f6; padding:2px 6px; border-radius:4px;">{{ json_encode($context, JSON_PRETTY_PRINT) }}</code>
    </p>
    @endif

    <a href="{{ $deploymentUrl }}" class="btn">View Deployment →</a>
  </div>
  <div class="footer">
    <p>Dot.Agents AI Workforce Platform</p>
    <p>Manage your notification preferences in your account settings.</p>
  </div>
</div>
</body>
</html>
