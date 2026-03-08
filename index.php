<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>299본부 성과관리 | 실시간 업적판</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/229/assets/css/app.css?v=22">
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div>
                        <div class="logo-text">299본부</div>
                        <div class="logo-subtitle">프라임에셋 성과관리 시스템</div>
                    </div>
                </div>
                <div class="header-actions flex items-center gap-2">
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <span>LIVE</span>
                        <span id="last-updated">--:--:--</span>
                    </div>
                    <button id="theme-toggle" class="theme-toggle" title="테마 변경">
                        <div class="theme-toggle-track">
                            <div class="theme-toggle-thumb"></div>
                        </div>
                    </button>
                    <button id="btn-refresh" class="btn btn-secondary btn-sm" title="새로고침">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                        <span class="btn-text">새로고침</span>
                    </button>
                    <button id="btn-save-image" class="btn btn-primary btn-sm" title="이미지 저장">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span class="btn-text">이미지 저장</span>
                    </button>
                    <a href="/229/admin/login.php" class="btn btn-secondary btn-sm" title="관리자">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="btn-text">관리자</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-container" id="dashboard-content">
        <div class="container">
            <!-- 분기 헤더 -->
            <div class="quarter-header mb-4" id="quarter-header">
                <div class="quarter-selector">
                    <select id="year-select" class="form-control quarter-select">
                        <option value="">연도 선택</option>
                    </select>
                    <select id="quarter-select" class="form-control quarter-select">
                        <option value="1">1분기</option>
                        <option value="2">2분기</option>
                        <option value="3">3분기</option>
                        <option value="4">4분기</option>
                    </select>
                    <button class="btn btn-secondary btn-sm" id="btn-current-quarter">현재 분기</button>
                </div>
                <h1 class="quarter-title" id="quarter-title">2026년 1분기 평가제</h1>
                <p class="quarter-subtitle">299본부 성과관리 시스템</p>
            </div>

            <!-- 빠른 이동 네비게이션 -->
            <div class="quick-nav mb-4">
                <button class="quick-nav-btn" onclick="document.getElementById('guinness-section').scrollIntoView({behavior: 'smooth'})">
                    <span>🏆</span> 기네스
                </button>
                <button class="quick-nav-btn" onclick="document.getElementById('hall-of-fame-section').scrollIntoView({behavior: 'smooth'})">
                    <span>🏅</span> 부문별 1위
                </button>
                <button class="quick-nav-btn active" onclick="document.getElementById('ranking-section').scrollIntoView({behavior: 'smooth'})">
                    <span>📊</span> 실시간 순위
                </button>
            </div>

            <!-- 기네스 명예의 전당 (역대 최고 기록) -->
            <div class="card mb-4 guinness-section" id="guinness-section">
                <div class="card-header">
                    <h2 class="card-title">
                        <span style="font-size: 1.5rem;">🏆</span>
                        299본부 기네스 명예의 전당
                    </h2>
                    <button class="btn btn-secondary btn-sm" id="btn-show-all-records">
                        전체 기록 보기
                    </button>
                </div>
                <div class="card-body">
                    <div class="guinness-grid" id="guinness-records">
                        <div class="empty-state">데이터를 불러오는 중...</div>
                    </div>
                </div>
            </div>

            <!-- 통계 요약 -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-label">활동 설계사</div>
                    <div class="stat-value" id="stat-total-agents">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">총 조기가동</div>
                    <div class="stat-value gold" id="stat-total-early">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">총 월납보험료</div>
                    <div class="stat-value gold" id="stat-total-monthly">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">총 계약건수</div>
                    <div class="stat-value" id="stat-total-contracts">-</div>
                </div>
            </div>

            <!-- 이번 분기 명예의 전당 -->
            <div class="card mb-4" id="hall-of-fame-section">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                        이번 분기 부문별 1위
                    </h2>
                    <div class="flex gap-2">
                        <button class="btn btn-secondary btn-sm" id="btn-event-modal">이벤트 확인</button>
                        <button class="btn btn-secondary btn-sm" id="btn-award-modal">분기시상</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="hall-of-fame" id="hall-of-fame">
                        <!-- 명예의 전당 카드가 여기에 렌더링됩니다 -->
                        <div class="empty-state">데이터를 불러오는 중...</div>
                    </div>
                </div>
            </div>

            <!-- 순위 테이블 -->
            <div class="card" id="ranking-section">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span id="ranking-title">실시간 순위 (분기)</span>
                    </h2>
                    <div class="flex gap-1" style="align-items: center;">
                        <button class="btn btn-secondary btn-sm" data-period="quarter">분기</button>
                        <button class="btn btn-primary btn-sm active" data-period="month">월별</button>
                        <select id="month-select" class="form-control" style="max-width: 80px;">
                            <option value="1">1월</option>
                            <option value="2">2월</option>
                            <option value="3">3월</option>
                            <option value="4">4월</option>
                            <option value="5">5월</option>
                            <option value="6">6월</option>
                            <option value="7">7월</option>
                            <option value="8">8월</option>
                            <option value="9">9월</option>
                            <option value="10">10월</option>
                            <option value="11">11월</option>
                            <option value="12">12월</option>
                        </select>
                        <button class="btn btn-secondary btn-sm" id="btn-toggle-scores">점수확인</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ranking-table-wrapper">
                        <table class="ranking-table ranking-table-compact">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">순위</th>
                                    <th style="min-width: 130px; text-align: left;">설계사</th>
                                    <th>조기가동</th>
                                    <th>월납보험료</th>
                                    <th>건수</th>
                                    <th>3W</th>
                                    <th>성장률</th>
                                    <th>이벤트</th>
                                    <th>근태</th>
                                    <th>최종합계</th>
                                </tr>
                            </thead>
                            <tbody id="ranking-tbody">
                                <tr>
                                    <td colspan="10" class="empty-state">데이터를 불러오는 중...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 플로팅 버튼 -->
    <div class="floating-nav" id="floating-nav">
        <button class="floating-btn" onclick="document.getElementById('ranking-section').scrollIntoView({behavior: 'smooth'})" title="실시간 순위로 이동">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>실시간 순위</span>
        </button>
        <button class="floating-btn floating-btn-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="맨 위로">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.875rem;">
        <p>© 2026 프라임에셋 299본부. All rights reserved.</p>
    </footer>

    <script src="/229/assets/js/dashboard.js?v=26"></script>
</body>
</html>
