/**
 * 299본부 성과관리 CRM - 대시보드 JavaScript
 */

const Dashboard = {
    refreshInterval: 5000,
    refreshTimer: null,
    apiBase: '/229/api',
    currentPeriod: 'quarter',
    currentMonth: new Date().getMonth() + 1,
    currentSort: 'total_score',
    currentYear: new Date().getFullYear(),
    currentQuarter: Math.ceil((new Date().getMonth() + 1) / 3),
    currentQuarterId: null,
    quarters: [],

    init() {
        // 테마 초기화
        this.initTheme();

        // 현재 월 기본 선택
        const monthSelect = document.getElementById('month-select');
        if (monthSelect) {
            monthSelect.value = this.currentMonth;
        }

        this.loadQuarters().then(() => {
            this.loadQuarterInfo();
            this.loadGuinnessRecords();
            this.loadHallOfFame();
            this.loadRankings();
            this.loadStats();
            this.startAutoRefresh();
            this.bindEvents();
        }).catch(error => {
            console.error('Init error:', error);
            // 기본값으로 로드 시도
            this.loadRankings();
            this.loadHallOfFame();
            this.loadStats();
            this.bindEvents();
        });
    },

    // 테마 초기화 (디폴트: 라이트)
    initTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    },

    // 테마 전환
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        if (newTheme === 'light') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        localStorage.setItem('theme', newTheme);
    },

    // 분기 목록 로드
    async loadQuarters() {
        try {
            const result = await this.fetchAPI('/dashboard/quarters.php');
            if (result.success && result.data) {
                this.quarters = result.data.quarters || [];
                const years = result.data.years || [];
                const current = result.data.current;

                // 연도 셀렉트 채우기
                const yearSelect = document.getElementById('year-select');
                if (yearSelect && years.length > 0) {
                    yearSelect.innerHTML = years.map(y =>
                        `<option value="${y}">${y}년</option>`
                    ).join('');
                }

                // 현재 분기 설정
                if (current) {
                    this.currentYear = parseInt(current.year);
                    this.currentQuarter = parseInt(current.quarter);
                    this.currentQuarterId = parseInt(current.id);
                }

                // 셀렉트 박스 값 설정
                if (yearSelect) {
                    yearSelect.value = this.currentYear;
                }

                // 해당 연도의 등록된 분기만 표시
                this.updateQuarterSelect(this.currentYear);

                const quarterSelect = document.getElementById('quarter-select');
                if (quarterSelect) {
                    quarterSelect.value = this.currentQuarter;
                }
            }
        } catch (error) {
            console.error('loadQuarters error:', error);
        }
    },

    // 연도에 해당하는 분기만 셀렉트박스에 표시
    updateQuarterSelect(year) {
        const quarterSelect = document.getElementById('quarter-select');
        if (!quarterSelect) return;

        // 해당 연도에 등록된 분기 필터링
        const availableQuarters = this.quarters
            .filter(q => q.year == year)
            .map(q => parseInt(q.quarter))
            .sort((a, b) => a - b);

        if (availableQuarters.length === 0) {
            quarterSelect.innerHTML = '<option value="">등록된 분기 없음</option>';
            quarterSelect.disabled = true;
            return;
        }

        quarterSelect.disabled = false;
        quarterSelect.innerHTML = availableQuarters.map(q =>
            `<option value="${q}">${q}분기</option>`
        ).join('');

        // 현재 선택된 분기가 목록에 없으면 첫 번째 분기 선택
        if (!availableQuarters.includes(this.currentQuarter)) {
            this.currentQuarter = availableQuarters[0];
        }
        quarterSelect.value = this.currentQuarter;
    },

    // 선택된 분기 ID 찾기
    findQuarterId(year, quarter) {
        const found = this.quarters.find(q => q.year == year && q.quarter == quarter);
        return found ? found.id : null;
    },

    // API 호출 헬퍼
    async fetchAPI(endpoint, options = {}) {
        try {
            const response = await fetch(this.apiBase + endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: '서버 연결 오류' };
        }
    },

    // 분기 정보 로드
    async loadQuarterInfo() {
        // 선택된 연도/분기 기준으로 타이틀 업데이트
        this.updateElement('quarter-title', `${this.currentYear}년 ${this.currentQuarter}분기 평가제`);
    },

    // 기네스 기록 로드
    async loadGuinnessRecords() {
        const result = await this.fetchAPI('/dashboard/guinness.php');

        if (result.success && result.data.guinness) {
            this.renderGuinnessRecords(result.data.guinness);
        }
    },

    // 기네스 기록 렌더링
    renderGuinnessRecords(data) {
        const container = document.getElementById('guinness-records');
        if (!container) return;

        const categories = {
            premium: { label: '월납보험료 역대 최고', icon: '💰', unit: '' },
            count: { label: '월 건수 역대 최고', icon: '📊', unit: '' }
        };

        let html = '';

        for (const [key, category] of Object.entries(categories)) {
            const record = data[key];

            if (record) {
                const hasPhoto = !!record.profile_image;
                const cardClass = hasPhoto ? 'guinness-card guinness-card-premium' : 'guinness-card';
                const profileImg = hasPhoto
                    ? `<img src="/229/uploads/profiles/${record.profile_image}" alt="${record.name}">`
                    : `<span class="guinness-profile-placeholder">👤</span>`;

                html += `
                    <div class="${cardClass}">
                        ${hasPhoto ? '<div class="guinness-glow"></div>' : ''}
                        ${hasPhoto ? '<div class="guinness-sparkles"><span></span><span></span><span></span><span></span><span></span></div>' : ''}
                        <div class="guinness-crown">${hasPhoto ? '👑' : '🏆'}</div>
                        <div class="guinness-category">${category.icon} ${category.label}</div>
                        <div class="guinness-profile ${hasPhoto ? 'guinness-profile-glow' : ''}">${profileImg}</div>
                        <div class="guinness-name">${record.name}</div>
                        <div class="guinness-team">${record.team_name || record.position || ''}</div>
                        <div class="guinness-record">${record.record_formatted}</div>
                        <div class="guinness-record-label">🏆 역대 최고 기록</div>
                    </div>
                `;
            } else {
                html += `
                    <div class="guinness-card">
                        <div class="guinness-category">${category.icon} ${category.label}</div>
                        <div class="guinness-profile">
                            <span class="guinness-profile-placeholder">-</span>
                        </div>
                        <div class="guinness-name">-</div>
                        <div class="guinness-team">기록 없음</div>
                        <div class="guinness-record">-</div>
                    </div>
                `;
            }
        }

        container.innerHTML = html;
    },

    // 전체 기록 보기 모달
    async showAllRecords() {
        const result = await this.fetchAPI('/dashboard/guinness.php?all=true');

        if (!result.success || !result.data.all_records) {
            this.showToast('기록을 불러올 수 없습니다.', 'error');
            return;
        }

        const records = result.data.all_records;

        let html = `
            <div class="modal-overlay active" id="records-modal">
                <div class="modal" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3 class="modal-title">🏆 전체 기네스 기록</h3>
                        <button class="modal-close" onclick="Dashboard.closeRecordsModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="records-list">
        `;

        if (records.length === 0) {
            html += `<div class="empty-state">등록된 기록이 없습니다.</div>`;
        } else {
            records.forEach((record, index) => {
                const profileImg = record.profile_image
                    ? `<img src="/229/uploads/profiles/${record.profile_image}" alt="${record.name}">`
                    : `<span style="color: var(--text-muted);">👤</span>`;

                html += `
                    <div class="record-item">
                        <div class="record-rank">${index + 1}</div>
                        <div class="record-profile">${profileImg}</div>
                        <div class="record-info">
                            <div class="record-name">${record.name}</div>
                            <div class="record-team">${record.team_name || record.position || ''}</div>
                        </div>
                        <div class="record-values">
                            <div class="record-premium">${this.formatNumber(record.best_monthly_premium)}원</div>
                            <div class="record-count">${record.best_monthly_count}건</div>
                        </div>
                    </div>
                `;
            });
        }

        html += `
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="Dashboard.closeRecordsModal()">닫기</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    },

    // 전체 기록 모달 닫기
    closeRecordsModal() {
        const modal = document.getElementById('records-modal');
        if (modal) {
            modal.remove();
        }
    },

    // 명예의 전당 로드
    async loadHallOfFame() {
        const quarterId = this.findQuarterId(this.currentYear, this.currentQuarter);
        let url = '/dashboard/hall-of-fame.php';
        if (quarterId) {
            url += `?quarter_id=${quarterId}`;
        }
        const result = await this.fetchAPI(url);

        if (result.success && result.data.hall_of_fame) {
            this.renderHallOfFame(result.data.hall_of_fame);
        }
    },

    // 명예의 전당 렌더링
    renderHallOfFame(data) {
        const container = document.getElementById('hall-of-fame');
        if (!container) return;

        const categories = {
            early: { label: '조기가동왕', icon: '🚀' },
            monthly: { label: '월납왕', icon: '💰' },
            count: { label: '건수왕', icon: '📊' },
            total: { label: '종합왕', icon: '👑' }
        };

        let html = '';

        for (const [key, category] of Object.entries(categories)) {
            const fame = data[key];

            if (fame) {
                const profileImg = fame.profile_image
                    ? `<img src="/229/uploads/profiles/${fame.profile_image}" alt="${fame.name}">`
                    : `<span class="fame-profile-placeholder">👤</span>`;

                const valueFormatted = key === 'count'
                    ? `${fame.value}건`
                    : `${this.formatNumber(fame.value)}원`;

                html += `
                    <div class="fame-card">
                        <div class="fame-category">${category.icon} ${category.label}</div>
                        <div class="fame-profile">${profileImg}</div>
                        <div class="fame-name">${fame.name}</div>
                        <div class="fame-team">${fame.team_name || fame.position || ''}</div>
                        <div class="fame-score">${this.formatNumber(fame.score, 1)}점</div>
                        <div class="fame-value">${valueFormatted}</div>
                    </div>
                `;
            } else {
                html += `
                    <div class="fame-card">
                        <div class="fame-category">${category.icon} ${category.label}</div>
                        <div class="fame-profile">
                            <span class="fame-profile-placeholder">-</span>
                        </div>
                        <div class="fame-name">-</div>
                        <div class="fame-team">데이터 없음</div>
                        <div class="fame-score">-</div>
                    </div>
                `;
            }
        }

        container.innerHTML = html;
    },

    // 순위 로드
    async loadRankings(orderBy = null) {
        if (orderBy) {
            this.currentSort = orderBy;
        }

        // 선택된 분기의 ID 찾기
        const quarterId = this.findQuarterId(this.currentYear, this.currentQuarter);

        let url = `/dashboard/ranking.php?order_by=${this.currentSort}&period=${this.currentPeriod}`;
        if (quarterId) {
            url += `&quarter_id=${quarterId}`;
        }
        if (this.currentPeriod === 'month') {
            url += `&month=${this.currentMonth}`;
        }

        const result = await this.fetchAPI(url);

        if (result.success && result.data.rankings) {
            this.renderRankings(result.data.rankings);
            this.updateTimestamp(result.data.updated_at);
            this.updateRankingTitle();
        }
    },

    // 순위 타이틀 업데이트
    updateRankingTitle() {
        const titleEl = document.getElementById('ranking-title');
        if (titleEl) {
            if (this.currentPeriod === 'month') {
                titleEl.textContent = `실시간 순위 (${this.currentMonth}월)`;
            } else {
                titleEl.textContent = `실시간 순위 (${this.currentYear}년 ${this.currentQuarter}분기)`;
            }
        }

        // 분기 헤더 타이틀 업데이트
        const quarterTitle = document.getElementById('quarter-title');
        if (quarterTitle) {
            quarterTitle.textContent = `${this.currentYear}년 ${this.currentQuarter}분기 평가제`;
        }
    },

    // N년차 계산
    calculateYearsOfService(joinDate) {
        if (!joinDate) return '';
        const join = new Date(joinDate);
        const today = new Date();
        const years = Math.floor((today - join) / (365.25 * 24 * 60 * 60 * 1000)) + 1;
        return `${years}년차`;
    },

    // 순위 렌더링
    renderRankings(rankings) {
        const tbody = document.getElementById('ranking-tbody');
        if (!tbody) return;

        if (rankings.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        등록된 실적이 없습니다.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';

        rankings.forEach((rank, index) => {
            const rankClass = rank.rank <= 3 ? `rank-${rank.rank}` : '';
            const rankIcon = rank.rank === 1 ? '🥇' : rank.rank === 2 ? '🥈' : rank.rank === 3 ? '🥉' : '';

            const profileImg = rank.profile_image
                ? `<img src="/229/uploads/profiles/${rank.profile_image}" alt="${rank.name}">`
                : `<span class="agent-profile-placeholder">👤</span>`;

            // 성장률 색상 결정 (양수: 빨강, 음수: 파랑)
            const growthScore = parseFloat(rank.growth_score) || 0;
            const growthClass = growthScore > 0 ? 'growth-positive' : growthScore < 0 ? 'growth-negative' : '';

            // N년차 계산
            const yearsOfService = this.calculateYearsOfService(rank.join_date);
            const teamInfo = rank.team_name || rank.position || '';
            const subInfo = yearsOfService ? `${teamInfo} ${yearsOfService}` : teamInfo;

            // 3W 계산 (몇 주차인지)
            const threeWWeeks = rank.three_w_weeks || '-';

            // 모바일용 세부 정보
            const detailsText = `월납 ${this.formatNumber(rank.monthly_score, 1)} · 조기 ${this.formatNumber(rank.early_score, 1)} · 건수 ${this.formatNumber(rank.count_score, 1)} · 3W ${this.formatNumber(rank.three_w_score, 1)}`;

            html += `
                <tr class="${rankClass}" data-details="${detailsText}">
                    <td class="rank-cell">${rankIcon} ${rank.rank}</td>
                    <td>
                        <div class="agent-cell">
                            <div class="agent-profile">${profileImg}</div>
                            <div class="agent-info">
                                <div class="agent-name">${rank.name}</div>
                                <div class="agent-team">${subInfo}</div>
                            </div>
                        </div>
                    </td>
                    <td class="score-cell-dual">
                        <div class="score-value">${this.formatNumber(rank.monthly_cumulative)}</div>
                        <div class="score-point">${this.formatNumber(rank.monthly_score, 1)}점</div>
                    </td>
                    <td class="score-cell-dual">
                        <div class="score-value">${this.formatNumber(rank.early_cumulative)}</div>
                        <div class="score-point">${this.formatNumber(rank.early_score, 1)}점</div>
                    </td>
                    <td class="score-cell-dual">
                        <div class="score-value">${rank.total_count}건</div>
                        <div class="score-point">${this.formatNumber(rank.count_score, 1)}점</div>
                    </td>
                    <td class="score-cell-dual">
                        <div class="score-value">${threeWWeeks}주</div>
                        <div class="score-point">${this.formatNumber(rank.three_w_score, 1)}점</div>
                    </td>
                    <td class="score-cell-dual ${growthClass}">
                        <div class="score-value">${this.formatNumber(rank.growth_rate, 1)}%</div>
                        <div class="score-point">${this.formatNumber(rank.growth_score, 1)}점</div>
                    </td>
                    <td class="score-cell-dual">
                        <div class="score-value">${rank.attendance_status || '-'}</div>
                        <div class="score-point">${this.formatNumber(rank.attendance_score, 1)}점</div>
                    </td>
                    <td class="score-cell score-highlight">${this.formatNumber(rank.total_score, 1)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    },

    // 통계 로드
    async loadStats() {
        const quarterId = this.findQuarterId(this.currentYear, this.currentQuarter);
        let url = '/dashboard/stats.php';
        if (quarterId) {
            url += `?quarter_id=${quarterId}`;
        }
        const result = await this.fetchAPI(url);

        if (result.success && result.data.overview) {
            this.renderStats(result.data);
        }
    },

    // 통계 렌더링
    renderStats(data) {
        const overview = data.overview;

        this.updateElement('stat-total-agents', overview.total_agents);
        this.updateElement('stat-total-early', this.formatNumber(overview.total_early));
        this.updateElement('stat-total-monthly', this.formatNumber(overview.total_monthly));
        this.updateElement('stat-total-contracts', overview.total_contracts);
    },

    // 엘리먼트 업데이트
    updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    },

    // 타임스탬프 업데이트
    updateTimestamp(timestamp) {
        const el = document.getElementById('last-updated');
        if (el) {
            const date = new Date(timestamp);
            el.textContent = date.toLocaleTimeString('ko-KR');
        }
    },

    // 숫자 포맷팅
    formatNumber(num, decimals = 0) {
        if (num === null || num === undefined) return '-';
        return Number(num).toLocaleString('ko-KR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    },

    // 자동 새로고침 시작
    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadRankings();
            this.loadHallOfFame();
            this.loadStats();
        }, this.refreshInterval);
    },

    // 자동 새로고침 중지
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    },

    // 이벤트 바인딩
    bindEvents() {
        // 연도 선택
        const yearSelect = document.getElementById('year-select');
        if (yearSelect) {
            yearSelect.addEventListener('change', () => {
                this.currentYear = parseInt(yearSelect.value);
                this.updateQuarterSelect(this.currentYear);
                this.loadQuarterInfo();
                this.loadRankings();
                this.loadHallOfFame();
                this.loadStats();
            });
        }

        // 분기 선택
        const quarterSelect = document.getElementById('quarter-select');
        if (quarterSelect) {
            quarterSelect.addEventListener('change', () => {
                this.currentQuarter = parseInt(quarterSelect.value);
                this.loadQuarterInfo();
                this.loadRankings();
                this.loadHallOfFame();
                this.loadStats();
            });
        }

        // 현재 분기 버튼
        const currentQuarterBtn = document.getElementById('btn-current-quarter');
        if (currentQuarterBtn) {
            currentQuarterBtn.addEventListener('click', async () => {
                await this.loadQuarters();
                this.loadQuarterInfo();
                this.loadRankings();
                this.loadHallOfFame();
                this.loadStats();
            });
        }

        // 정렬 버튼
        document.querySelectorAll('[data-sort]').forEach(btn => {
            btn.addEventListener('click', () => {
                const orderBy = btn.dataset.sort;
                this.loadRankings(orderBy);

                // 활성화 표시
                document.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // 기간 토글 버튼 (분기/월별)
        document.querySelectorAll('[data-period]').forEach(btn => {
            btn.addEventListener('click', () => {
                const period = btn.dataset.period;
                this.currentPeriod = period;

                // 버튼 스타일 업데이트
                document.querySelectorAll('[data-period]').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-secondary');
                });
                btn.classList.remove('btn-secondary');
                btn.classList.add('active', 'btn-primary');

                // 월 선택 드롭다운 표시/숨김
                const monthSelect = document.getElementById('month-select');
                if (monthSelect) {
                    monthSelect.style.display = period === 'month' ? 'block' : 'none';
                }

                this.loadRankings();
            });
        });

        // 월 선택
        const monthSelect = document.getElementById('month-select');
        if (monthSelect) {
            monthSelect.addEventListener('change', () => {
                this.currentMonth = parseInt(monthSelect.value);
                this.loadRankings();
            });
        }

        // 이미지 저장 버튼
        const saveBtn = document.getElementById('btn-save-image');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveAsImage());
        }

        // 새로고침 버튼
        const refreshBtn = document.getElementById('btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadRankings();
                this.loadHallOfFame();
                this.loadGuinnessRecords();
                this.loadStats();
                this.showToast('새로고침 완료', 'success');
            });
        }

        // 기네스 전체 기록 보기 버튼
        const showAllRecordsBtn = document.getElementById('btn-show-all-records');
        if (showAllRecordsBtn) {
            showAllRecordsBtn.addEventListener('click', () => this.showAllRecords());
        }

        // 테마 토글 버튼
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // 플로팅 네비게이션 스크롤 이벤트
        const floatingNav = document.getElementById('floating-nav');
        if (floatingNav) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    floatingNav.classList.add('visible');
                } else {
                    floatingNav.classList.remove('visible');
                }
            });
        }
    },

    // 이미지로 저장 (html2canvas 필요)
    async saveAsImage() {
        if (typeof html2canvas === 'undefined') {
            this.showToast('이미지 저장 기능을 사용할 수 없습니다.', 'error');
            return;
        }

        const element = document.getElementById('dashboard-content');
        if (!element) return;

        try {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const canvas = await html2canvas(element, {
                backgroundColor: isDark ? '#0f172a' : '#f8fafc',
                scale: 2
            });

            const link = document.createElement('a');
            const date = new Date().toISOString().split('T')[0];
            link.download = `299본부_업적판_${date}.png`;
            link.href = canvas.toDataURL();
            link.click();

            this.showToast('이미지가 저장되었습니다.', 'success');
        } catch (error) {
            console.error('Image save error:', error);
            this.showToast('이미지 저장에 실패했습니다.', 'error');
        }
    },

    // 토스트 메시지
    showToast(message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// DOM 로드 후 초기화
document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
});
