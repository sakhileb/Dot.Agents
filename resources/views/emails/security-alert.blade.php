<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Alert — Dot.Agents</title>
<style>
  body { margin: 0; padding: 0; background: #f9f9f7; font-family: 'Inter', Arial, sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(220,38,38,0.12); }
  .header { background: #dc2626; padding: 32px 40px; }
  .header h1 { color: #fff; font-size: 20px; margin: 0; font-weight: 700; }
  .header p { color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px; }
  .body { padding: 40px; }
  .severity-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 24px; }
  .severity-critical { background: #fee2e2; color: #991b1b; }
  .severity-high { background: #fef3c7; color: #92400e; }
  .severity-medium { background: #fef9c3; color: #78350f; }
  .severity-low { background: #dcfce7; color: #166534; }
  .detail-block { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px 20px; margin: 24px 0; }
  .detail-block p { margin: 4px 0; font-size: 14px; color: #374151; }
  .body p { color: #374151; line-height: 1.6; margin: 0 0 16px; }
  .btn { display: inline-block; background: #dc2626; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; margin-top: 16px; }
  .footer { background: #f9f9f7; padding: 24px 40px; text-align: center; }
  .footer p { color: #9ca3af; font-size: 12px; margin: 4px 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>🔒 Dot.Agents Security Alert</h1>
    <p>{{ $organizationName }} · {{ $detectedAt->format('M j, Y g:i A T') }}</p>
  </div>
  <div class="body">
    <span class="severity-badge severity-{{ $severity }}">{{ strtoupper($severity) }} SEVERITY</span>

    <p>A security event has been detected on the Dot.Agents platform that requires your immediate attention.</p>

    <div class="detail-block">
      <p><strong>Event Type:</strong> {{ str_replace('_', ' ', ucwords($eventType, '_')) }}</p>
      <p><strong>Organization:</strong> {{ $organizationName }}</p>
      <p><strong>Detected:</strong> {{ $detectedAt->format('M j, Y g:i:s A') }}</p>
      @if($description)
      <p><strong>Details:</strong> {{ $description }}</p>
      @endif
    </div>

    <p>Please review this event immediately and take appropriate action. If this was expected activity, you can dismiss it from the Security Center.</p>

    <a href="{{ $reviewUrl }}" class="btn">Review in Security Center →</a>
  </div>
  <div class="footer">
    <p>Dot.Agents AI Workforce Platform — Security Operations</p>
    <p>This alert was sent because you are a security contact for this organization.</p>
  </div>
</div>
</body>
</html>
