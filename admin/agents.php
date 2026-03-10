<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>설계사 관리 | 299본부</title>
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
                <a href="/admin/agents.php" class="nav-item active">
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
                <h1>설계사 관리</h1>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" id="btn-manage-teams">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        팀 관리
                    </button>
                    <button class="btn btn-primary" id="btn-add-agent">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        설계사 등록
                    </button>
                </div>
            </div>

            <!-- 검색 및 필터 -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex gap-2 flex-wrap items-center">
                        <input
                            type="text"
                            id="search-input"
                            class="form-control"
                            placeholder="이름 검색"
                            style="max-width: 300px;"
                        >
                        <select id="filter-team" class="form-control" style="max-width: 200px;">
                            <option value="">전체 팀</option>
                        </select>
                        <select id="filter-status" class="form-control" style="max-width: 150px;">
                            <option value="">전체</option>
                            <option value="1">활성</option>
                            <option value="0">비활성</option>
                        </select>
                        <button class="btn btn-secondary" id="btn-search">검색</button>
                    </div>
                </div>
            </div>

            <!-- 설계사 목록 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">설계사 목록</h3>
                    <span class="text-muted" id="agent-count">0명</span>
                </div>
                <div class="card-body">
                    <div class="ranking-table-wrapper">
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">번호</th>
                                    <th style="text-align: left;">이름</th>
                                    <th>팀</th>
                                    <th>직급</th>
                                    <th>입사일</th>
                                    <th>상태</th>
                                    <th style="width: 120px;">관리</th>
                                </tr>
                            </thead>
                            <tbody id="agents-tbody">
                                <tr>
                                    <td colspan="7" class="empty-state">데이터를 불러오는 중...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 설계사 등록/수정 모달 -->
    <div class="modal-overlay" id="agent-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">설계사 등록</h3>
                <button class="modal-close" onclick="Admin.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="agent-form">
                    <input type="hidden" id="agent-id">

                    <!-- 프로필 이미지 -->
                    <div class="form-group" style="text-align: center;">
                        <label class="form-label">프로필 사진</label>
                        <div id="agent-image-preview" style="width: 100px; height: 100px; border-radius: 50%; background: var(--bg-tertiary); margin: 0.5rem auto; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid var(--border-color);">
                            <span style="color: var(--text-muted); font-size: 2rem;">👤</span>
                        </div>
                        <input type="file" id="agent-image" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('agent-image').click()">사진 선택</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-name">이름 *</label>
                        <input type="text" id="agent-name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-team">팀</label>
                        <select id="agent-team" class="form-control">
                            <option value="">선택 안함</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-position">직급</label>
                        <select id="agent-position" class="form-control">
                            <option value="FC">FC</option>
                            <option value="팀장">팀장</option>
                            <option value="지사장">지사장</option>
                            <option value="본부장">본부장</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-join-date">입사일</label>
                        <input type="text" id="agent-join-date" class="form-control date-input" placeholder="2024-02-03" maxlength="10">
                    </div>

                    <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
                    <h4 style="margin-bottom: 1rem; color: var(--gold);">실적 정보</h4>

                    <div class="form-group">
                        <label class="form-label" for="agent-prev-avg">전분기 평균 월납실적 (원)</label>
                        <input type="text" id="agent-prev-avg" class="form-control money-input" value="" placeholder="성장률 계산에 사용됩니다">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-best-premium">역대 최고 월납보험료 (원)</label>
                        <input type="text" id="agent-best-premium" class="form-control money-input" value="" placeholder="기네스 기록용">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent-best-count">역대 최고 월 건수</label>
                        <input type="text" id="agent-best-count" class="form-control" value="" placeholder="기네스 기록용" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" id="agent-active" checked> 활성 상태
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="Admin.closeModal()">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-agent">저장</button>
            </div>
        </div>
    </div>

    <!-- 팀 관리 모달 -->
    <div class="modal-overlay" id="teams-modal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">팀 관리</h3>
                <button class="modal-close" onclick="Admin.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="flex gap-2 mb-4">
                    <input type="text" id="new-team-name" class="form-control" placeholder="새 팀명 입력">
                    <button class="btn btn-primary" id="btn-add-team">추가</button>
                </div>
                <div id="teams-list">
                    <div class="empty-state">팀 목록을 불러오는 중...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="Admin.closeModal()">닫기</button>
                <button type="button" class="btn btn-primary" id="btn-save-all-teams">모두 저장</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin.js?v=14"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Admin.checkAuth();
            Admin.loadAgents();
            Admin.bindAgentEvents();
            Admin.bindTeamEvents();
        });
    </script>
</body>
</html>
