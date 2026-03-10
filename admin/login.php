<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 | 299본부</title>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=8">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: none;
        }
        .error-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card">
                <div class="card-body" style="padding: 2rem;">
                    <div class="login-header">
                        <div class="login-logo">299본부</div>
                        <div class="login-subtitle">관리자 로그인</div>
                    </div>

                    <div id="error-message" class="error-message"></div>

                    <form id="login-form">
                        <div class="form-group">
                            <label class="form-label" for="username">아이디</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="아이디를 입력하세요"
                                required
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">비밀번호</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="비밀번호를 입력하세요"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="submit-btn">
                            로그인
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="/" style="color: var(--text-muted); font-size: 0.875rem; text-decoration: none;">
                            ← 대시보드로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('submit-btn');
            const errorDiv = document.getElementById('error-message');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            btn.disabled = true;
            btn.textContent = '로그인 중...';
            errorDiv.classList.remove('show');

            try {
                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();

                if (result.success) {
                    // 토큰 저장
                    localStorage.setItem('auth_token', result.data.token);
                    localStorage.setItem('user', JSON.stringify(result.data.user));

                    // 쿠키에도 저장 (웹 페이지 인증용)
                    document.cookie = `auth_token=${result.data.token}; path=/; max-age=86400`;

                    // 관리자 페이지로 이동
                    window.location.href = '/admin/index.php';
                } else {
                    errorDiv.textContent = result.message;
                    errorDiv.classList.add('show');
                }
            } catch (error) {
                errorDiv.textContent = '서버 연결에 실패했습니다.';
                errorDiv.classList.add('show');
            }

            btn.disabled = false;
            btn.textContent = '로그인';
        });

        // 이미 로그인되어 있으면 리다이렉트
        const token = localStorage.getItem('auth_token');
        if (token) {
            fetch('/api/auth/verify.php', {
                headers: { 'Authorization': `Bearer ${token}` }
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    window.location.href = '/admin/index.php';
                }
            });
        }
    </script>
</body>
</html>
