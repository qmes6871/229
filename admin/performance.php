<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>실적 입력 | 299본부</title>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=8">
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
                <a href="/admin/index.php" class="nav-item">
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
                <a href="/admin/performance.php" class="nav-item active">
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
                <h1>실적 입력</h1>
            </div>

            <!-- 실적 요약 -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-label">입력 건수</div>
                    <div class="stat-value" id="summary-count">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">조기가동 합계</div>
                    <div class="stat-value gold" id="summary-early">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">월납보험료 합계</div>
                    <div class="stat-value gold" id="summary-monthly">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">계약건수 합계</div>
                    <div class="stat-value" id="summary-contracts">0</div>
                </div>
            </div>

            <!-- 필터 -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex gap-2 flex-wrap items-center">
                        <input
                            type="date"
                            id="filter-date"
                            class="form-control"
                            style="max-width: 200px;"
                        >
                        <select id="filter-perf-team" class="form-control" style="max-width: 180px;">
                            <option value="">전체 팀</option>
                        </select>
                        <input
                            type="text"
                            id="filter-perf-agent"
                            class="form-control"
                            placeholder="설계사 검색"
                            style="max-width: 150px;"
                        >
                        <button class="btn btn-secondary" id="btn-filter">조회</button>
                        <button class="btn btn-secondary" id="btn-today">오늘</button>
                        <div style="margin-left: auto; font-size: 0.875rem; color: var(--text-muted);">
                            <span style="color: var(--gold);">*</span> 1~7일 입력분은 조기가동으로 자동 분류됩니다.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 탭 메뉴 -->
            <div class="tabs mb-4">
                <div class="tab active" data-tab="performance">실적 입력</div>
                <div class="tab" data-tab="attendance">근태 관리</div>
            </div>

            <!-- 실적 목록 탭 -->
            <div class="tab-content" id="tab-performance">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">실적 입력</h3>
                        <span class="text-muted">활성화된 설계사가 자동으로 표시됩니다</span>
                    </div>
                    <div class="card-body">
                        <div class="ranking-table-wrapper">
                            <table class="ranking-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 130px; text-align: left;">설계사</th>
                                        <th style="width: 140px;">월납보험료</th>
                                        <th style="width: 70px;">건수</th>
                                        <th style="width: 70px;">이벤트</th>
                                        <th style="width: 100px;">월 누적</th>
                                        <th style="width: 80px;">조기가동</th>
                                        <th style="width: 60px;">총건수</th>
                                        <th style="width: 50px;">3W</th>
                                        <th style="width: 100px;">관리</th>
                                    </tr>
                                </thead>
                                <tbody id="performance-tbody">
                                    <tr>
                                        <td colspan="9" class="empty-state">데이터를 불러오는 중...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 근태 관리 탭 -->
            <div class="tab-content" id="tab-attendance" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            근태 입력
                        </h3>
                        <span class="text-muted">매달 말일에 1회 입력 (미출근: 0점 / 출근: 10점 / 만근: 20점)</span>
                    </div>
                    <div class="card-body">
                        <div class="flex gap-2 flex-wrap items-center mb-4">
                            <input
                                type="month"
                                id="attendance-date"
                                class="form-control"
                                style="max-width: 200px;"
                            >
                            <select id="filter-attendance-team" class="form-control" style="max-width: 180px;">
                                <option value="">전체 지사</option>
                            </select>
                            <button class="btn btn-secondary" id="btn-attendance-today">이번 달</button>
                            <button class="btn btn-primary" id="btn-save-all-attendance">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                근태 저장
                            </button>
                        </div>
                        <div class="attendance-grid" id="attendance-grid">
                            <div class="empty-state">데이터를 불러오는 중...</div>
                        </div>
                    </div>
                </div>

                <!-- 근태 현황 요약 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">이번 분기 근태 현황</h3>
                    </div>
                    <div class="card-body">
                        <div class="ranking-table-wrapper">
                            <table class="ranking-table">
                                <thead>
                                    <tr>
                                        <th style="text-align: left;">설계사</th>
                                        <th>상태</th>
                                        <th>근태여부</th>
                                        <th>근태점</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-summary-tbody">
                                    <tr>
                                        <td colspan="4" class="empty-state">데이터를 불러오는 중...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 실적 상세 모달 -->
    <div class="modal-overlay" id="detail-modal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span id="detail-agent-name">설계사</span> 실적 상세
                </h3>
                <button class="modal-close" onclick="Admin.closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- 설계사 정보 -->
                <div class="detail-agent-info mb-4">
                    <div class="flex items-center gap-2">
                        <div id="detail-agent-profile" class="agent-profile" style="width: 50px; height: 50px;">
                            <span class="agent-profile-placeholder">👤</span>
                        </div>
                        <div>
                            <div id="detail-agent-info" style="font-weight: 600;"></div>
                            <div id="detail-quarter-info" style="font-size: 0.875rem; color: var(--text-muted);"></div>
                        </div>
                    </div>
                </div>

                <!-- 이번 달 합계 -->
                <div class="stats-grid mb-4" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card">
                        <div class="stat-label">이번 달 조기가동</div>
                        <div class="stat-value gold" id="detail-total-early">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">이번 달 월납보험료</div>
                        <div class="stat-value gold" id="detail-total-monthly">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">이번 달 건수</div>
                        <div class="stat-value" id="detail-total-count">0</div>
                    </div>
                </div>

                <!-- 실적 추가 폼 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title" style="font-size: 0.9375rem;">실적 추가</h4>
                    </div>
                    <div class="card-body">
                        <div class="flex gap-2 flex-wrap items-end">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">날짜</label>
                                <input type="date" id="detail-add-date" class="form-control" style="width: 150px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">월납보험료</label>
                                <input type="text" id="detail-add-monthly" class="form-control money-input" style="width: 140px;" placeholder="0">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">건수</label>
                                <input type="number" id="detail-add-count" class="form-control" style="width: 80px;" value="0" min="0">
                            </div>
                            <button class="btn btn-primary" id="btn-detail-add">추가</button>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                            <span style="color: var(--gold);">*</span> 1~7일 입력분은 조기가동으로 자동 분류됩니다.
                        </div>
                    </div>
                </div>

                <!-- 실적 내역 -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="font-size: 0.9375rem;">실적 내역</h4>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">날짜</th>
                                    <th>구분</th>
                                    <th>조기가동</th>
                                    <th>월납보험료</th>
                                    <th>건수</th>
                                    <th style="width: 100px;">관리</th>
                                </tr>
                            </thead>
                            <tbody id="detail-performance-tbody">
                                <tr>
                                    <td colspan="6" class="empty-state">데이터를 불러오는 중...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="Admin.closeDetailModal()">닫기</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin.js?v=14"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Admin.checkAuth();
            Admin.loadPerformance();
            Admin.bindPerformanceEvents();
            Admin.bindAttendanceEvents();

            // 오늘 날짜 기본 설정
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('filter-date').value = today;

            // 이번 달 기본 설정 (근태용)
            const thisMonth = today.substring(0, 7);
            document.getElementById('attendance-date').value = thisMonth;
        });
    </script>
</body>
</html>
