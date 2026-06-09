<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to Dot.Agents</title>
<style>
  body { margin: 0; padding: 0; background: #f9f9f7; font-family: 'Inter', Arial, sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(61,46,160,0.08); }
  .header { background: linear-gradient(135deg, #1e1660 0%, #3d2ea0 100%); padding: 48px 40px; text-align: center; }
  .header .logo { font-size: 28px; font-weight: 800; color: #f5be1c; letter-spacing: -0.5px; margin: 0; }
  .header .tagline { color: rgba(255,255,255,0.75); font-size: 15px; margin: 12px 0 0; }
  .body { padding: 48px 40px; }
  .body h2 { color: #111827; font-size: 22px; font-weight: 700; margin: 0 0 16px; }
  .body p { color: #374151; line-height: 1.7; margin: 0 0 16px; font-size: 15px; }
  .highlight-box { background: #f5f3ff; border-left: 4px solid #3d2ea0; border-radius: 0 8px 8px 0; padding: 16px 20px; margin: 28px 0; }
  .highlight-box p { margin: 0; color: #3d2ea0; font-weight: 500; }
  .features { margin: 32px 0; }
  .feature { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
  .feature-icon { width: 36px; height: 36px; background: #f5be1c; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; line-height: 36px; text-align: center; }
  .feature-text h4 { margin: 0 0 4px; color: #111827; font-size: 14px; font-weight: 600; }
  .feature-text p { margin: 0; color: #6b7280; font-size: 13px; line-height: 1.5; }
  .cta-block { text-align: center; margin: 36px 0 28px; }
  .btn-primary { display: inline-block; background: #3d2ea0; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 15px; letter-spacing: 0.01em; }
  .btn-secondary { display: inline-block; color: #3d2ea0; text-decoration: none; padding: 10px 20px; font-size: 14px; font-weight: 500; }
  .footer { background: #f9f9f7; padding: 28px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
  .footer p { color: #9ca3af; font-size: 12px; margin: 4px 0; }
  .footer a { color: #6b7280; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <p class="logo">⚡ Dot.Agents</p>
    <p class="tagline">Your AI Workforce Platform</p>
  </div>

  <div class="body">
    <h2>Welcome aboard, {{ $userName }}! 🎉</h2>

    <p>
      Your organization <strong>{{ $orgName }}</strong> is now live on Dot.Agents.
      You're ready to hire, deploy, and manage your first AI workforce members.
    </p>

    <div class="highlight-box">
      <p>Your platform is ready. Start by exploring the Agent Marketplace and deploying your first AI agent.</p>
    </div>

    <div class="features">
      <div class="feature">
        <div class="feature-icon">🤖</div>
        <div class="feature-text">
          <h4>Agent Marketplace</h4>
          <p>Browse 50+ specialized AI agents across sales, finance, HR, legal, and operations.</p>
        </div>
      </div>
      <div class="feature">
        <div class="feature-icon">🛡️</div>
        <div class="feature-text">
          <h4>AI Governance</h4>
          <p>Every agent decision is logged, scored, and subject to human approval workflows.</p>
        </div>
      </div>
      <div class="feature">
        <div class="feature-icon">📊</div>
        <div class="feature-text">
          <h4>Performance Scorecards</h4>
          <p>Real-time 10-dimension health scores for every deployed agent in your workforce.</p>
        </div>
      </div>
    </div>

    <div class="cta-block">
      <a href="{{ $loginUrl }}" class="btn-primary">Launch Your AI Workforce →</a>
      <br><br>
      <a href="{{ $docsUrl }}" class="btn-secondary">Read the Documentation</a>
    </div>

    <p style="font-size:13px; color:#9ca3af; text-align:center;">
      Questions? Reply to this email or contact <a href="mailto:support@dotagents.com" style="color:#3d2ea0;">support@dotagents.com</a>
    </p>
  </div>

  <div class="footer">
    <p><strong style="color:#374151;">Dot.Agents</strong> — Enterprise AI Workforce Platform</p>
    <p>You're receiving this because you created an account on Dot.Agents.</p>
    <p style="margin-top:8px;"><a href="{{ $loginUrl }}">Login</a> · <a href="{{ $docsUrl }}">Docs</a> · <a href="mailto:support@dotagents.com">Support</a></p>
  </div>
</div>
</body>
</html>
