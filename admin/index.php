<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 | 299본부</title>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=17">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-text" style="font-size: 1.25rem;">299본부</div>
                <div class="logo-subtitle">관리자 시스템</div>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/index.php" class="nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    대시보드
                </a>
                <a href="/admin/agents.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    설계사 관리
                </a>
                <a href="/admin/performance.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="20" x2="12" y2="10"></line>
                        <line x1="18" y1="20" x2="18" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="16"></line>
                    </svg>
                    실적 입력
                </a>
                <a href="/" class="nav-item" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    업적판 보기
                </a>
            </nav>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    <span id="user-name">관리자</span>
                </div>
                <button id="btn-logout" class="btn btn-secondary btn-sm" style="width: 100%;">
                    로그아웃
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="flex justify-between items-center mb-4">
                <h1>대시보드</h1>
                <span style="color: var(--text-muted); font-size: 0.875rem;" id="current-date"></span>
            </div>

            <!-- 통계 카드 -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-label">등록 설계사</div>
                    <div class="stat-value" id="stat-agents">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">오늘 실적 입력</div>
                    <div class="stat-value gold" id="stat-today-entries">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">오늘 총 보험료</div>
                    <div class="stat-value" id="stat-today-premium">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">오늘 계약건수</div>
                    <div class="stat-value" id="stat-today-contracts">-</div>
                </div>
            </div>

            <!-- 빠른 메뉴 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">빠른 메뉴</h3>
                </div>
                <div class="card-body">
                    <div class="flex gap-2 flex-wrap">
                        <a href="/admin/agents.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            설계사 등록
                        </a>
                        <a href="/admin/performance.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            실적 입력
                        </a>
                        <a href="/" target="_blank" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            업적판 보기
                        </a>
                    </div>
                </div>
            </div>

            <!-- 분기 목표 점수 설정 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">분기 목표 점수 설정</h3>
                </div>
                <div class="card-body">
                    <div class="flex gap-3 items-end flex-wrap">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">현재 분기</label>
                            <select id="quarter-select" class="form-control" style="width: auto; min-width: 150px;"></select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">목표 점수</label>
                            <input type="number" id="target-score" class="form-control" style="width: 120px;" min="0" step="10" placeholder="200">
                        </div>
                        <button class="btn btn-primary" onclick="Admin.saveTargetScore()">저장</button>
                    </div>
                    <p style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--text-muted);">
                        설정한 목표 점수를 달성한 설계사는 실시간 순위에서 "달성" 표시가 나타납니다.
                    </p>
                </div>
            </div>

            <!-- 모달 콘텐츠 관리 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">업적판 모달 관리</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">이벤트 확인</label>
                            <textarea id="modal-event-content" class="form-control" rows="6" placeholder="이벤트 내용을 입력하세요..."></textarea>
                            <button class="btn btn-primary btn-sm mt-2" onclick="Admin.saveModalContent('event')">저장</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">분기시상</label>
                            <textarea id="modal-award-content" class="form-control" rows="6" placeholder="분기시상 내용을 입력하세요..."></textarea>
                            <button class="btn btn-primary btn-sm mt-2" onclick="Admin.saveModalContent('award')">저장</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 최근 실적 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">최근 실적 입력</h3>
                    <a href="/admin/performance.php" class="btn btn-secondary btn-sm">전체보기</a>
                </div>
                <div class="card-body">
                    <div class="ranking-table-wrapper">
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th>날짜</th>
                                    <th>설계사</th>
                                    <th>조기보험료</th>
                                    <th>월납보험료</th>
                                    <th>건수</th>
                                </tr>
                            </thead>
                            <tbody id="recent-performance">
                                <tr>
                                    <td colspan="5" class="empty-state">데이터를 불러오는 중...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin.js?v=16"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Admin.checkAuth();
            Admin.loadDashboard();
            Admin.loadModalContent();

            // 현재 날짜 표시
            const dateEl = document.getElementById('current-date');
            if (dateEl) {
                dateEl.textContent = new Date().toLocaleDateString('ko-KR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long'
                });
            }
        });
    </script>
</body>
</html>
