/**
 * 299본부 성과관리 CRM - 관리자 JavaScript
 */

const Admin = {
    apiBase: '/229/api',
    token: null,
    user: null,
    agents: [],
    teams: [],
    currentDetailAgentId: null,

    // 인증 확인
    checkAuth() {
        this.token = localStorage.getItem('auth_token');
        const userJson = localStorage.getItem('user');

        if (!this.token) {
            window.location.href = '/229/admin/login.php';
            return false;
        }

        if (userJson) {
            this.user = JSON.parse(userJson);
            const userNameEl = document.getElementById('user-name');
            if (userNameEl) {
                userNameEl.textContent = this.user.name || this.user.username;
            }
        }

        // 로그아웃 버튼
        const logoutBtn = document.getElementById('btn-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }

        return true;
    },

    // 로그아웃
    logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        document.cookie = 'auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT';
        window.location.href = '/229/admin/login.php';
    },

    // API 호출
    async fetchAPI(endpoint, options = {}) {
        try {
            const response = await fetch(this.apiBase + endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`,
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

    // 숫자 포맷 (콤마)
    formatNumber(num) {
        if (num === null || num === undefined) return '-';
        return Number(num).toLocaleString('ko-KR');
    },

    // 콤마 제거하여 숫자로 변환
    parseNumber(str) {
        if (!str) return 0;
        return parseInt(str.toString().replace(/,/g, ''), 10) || 0;
    },

    // N년차 계산
    calculateYearsOfService(joinDate) {
        if (!joinDate) return '';
        const join = new Date(joinDate);
        const today = new Date();
        const years = Math.floor((today - join) / (365.25 * 24 * 60 * 60 * 1000)) + 1;
        return `${years}년차`;
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
    },

    // 모달 열기
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    },

    // 모달 닫기
    closeModal() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.classList.remove('active');
        });
    },

    // 금액 입력 필드에 콤마 자동 포맷
    bindMoneyInputs() {
        document.querySelectorAll('.money-input').forEach(input => {
            // 이미 바인딩된 경우 스킵
            if (input.dataset.moneyBound) return;
            input.dataset.moneyBound = 'true';

            // 포커스 시 콤마 제거 (순수 숫자만 표시하여 편집 용이)
            input.addEventListener('focus', (e) => {
                let value = e.target.value.replace(/[^\d]/g, '');
                e.target.value = value;
            });

            // 포커스 해제 시 콤마 추가
            input.addEventListener('blur', (e) => {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value && Number(value) > 0) {
                    e.target.value = Number(value).toLocaleString('ko-KR');
                } else {
                    e.target.value = '';
                }
            });
        });
    },

    // ============================================
    // 대시보드
    // ============================================

    async loadDashboard() {
        // 통계 로드
        const stats = await this.fetchAPI('/dashboard/stats.php');
        if (stats.success) {
            document.getElementById('stat-agents').textContent = stats.data.overview.total_agents || '0';
            document.getElementById('stat-today-entries').textContent = stats.data.today.agents_with_performance || '0';
            document.getElementById('stat-today-premium').textContent = this.formatNumber(
                (parseFloat(stats.data.today.today_early) || 0) + (parseFloat(stats.data.today.today_monthly) || 0)
            );
            document.getElementById('stat-today-contracts').textContent = stats.data.today.today_contracts || '0';
        }

        // 최근 실적 로드
        const performances = await this.fetchAPI('/performance/list.php?limit=10');
        if (performances.success) {
            this.renderRecentPerformance(performances.data.performances);
        }
    },

    renderRecentPerformance(performances) {
        const tbody = document.getElementById('recent-performance');
        if (!tbody) return;

        if (!performances || performances.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">등록된 실적이 없습니다.</td></tr>';
            return;
        }

        let html = '';
        performances.slice(0, 10).forEach(p => {
            html += `
                <tr>
                    <td>${p.performance_date}</td>
                    <td>${p.agent_name}</td>
                    <td>${this.formatNumber(p.early_premium)}</td>
                    <td>${this.formatNumber(p.monthly_premium)}</td>
                    <td>${p.contract_count}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    // ============================================
    // 설계사 관리
    // ============================================

    async loadAgents() {
        const result = await this.fetchAPI('/agents/list.php');

        if (result.success) {
            this.agents = result.data.agents || [];
            this.teams = result.data.teams || [];
            this.renderAgents(this.agents);
            this.populateTeamSelects();
        }
    },

    renderAgents(agents) {
        const tbody = document.getElementById('agents-tbody');
        const countEl = document.getElementById('agent-count');

        if (!tbody) return;

        if (countEl) {
            countEl.textContent = `${agents.length}명`;
        }

        if (agents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">등록된 설계사가 없습니다.</td></tr>';
            return;
        }

        let html = '';
        agents.forEach((agent, index) => {
            const statusBadge = agent.is_active == 1
                ? '<span class="badge badge-success">활성</span>'
                : '<span class="badge badge-error">비활성</span>';

            const yearsOfService = this.calculateYearsOfService(agent.join_date);
            const joinDateDisplay = agent.join_date
                ? `${agent.join_date} (${yearsOfService})`
                : '-';

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td style="text-align: left;">
                        <div class="agent-cell">
                            <div class="agent-profile">
                                ${agent.profile_image
                                    ? `<img src="/229/uploads/profiles/${agent.profile_image}" alt="${agent.name}">`
                                    : '<span class="agent-profile-placeholder">👤</span>'}
                            </div>
                            <div class="agent-name">${agent.name}</div>
                        </div>
                    </td>
                    <td>${agent.team_name || '-'}</td>
                    <td>${agent.position || 'FC'}</td>
                    <td>${joinDateDisplay}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <button class="btn btn-secondary btn-sm" onclick="Admin.editAgent(${agent.id})">수정</button>
                            <button class="btn btn-secondary btn-sm" onclick="Admin.deleteAgent(${agent.id})" style="color: var(--error);">삭제</button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    populateTeamSelects() {
        const selects = ['filter-team', 'agent-team'];
        selects.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const firstOption = select.querySelector('option');
                select.innerHTML = '';
                if (firstOption) select.appendChild(firstOption);

                this.teams.forEach(team => {
                    const option = document.createElement('option');
                    option.value = team.id;
                    option.textContent = team.name;
                    select.appendChild(option);
                });
            }
        });
    },

    bindAgentEvents() {
        // 등록 버튼
        document.getElementById('btn-add-agent')?.addEventListener('click', () => {
            document.getElementById('modal-title').textContent = '설계사 등록';
            document.getElementById('agent-form').reset();
            document.getElementById('agent-id').value = '';
            document.getElementById('agent-active').checked = true;
            document.getElementById('agent-position').value = 'FC';
            this.resetImagePreview();
            this.openModal('agent-modal');
        });

        // 검색 버튼
        document.getElementById('btn-search')?.addEventListener('click', () => this.searchAgents());

        // 엔터키 검색
        document.getElementById('search-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.searchAgents();
        });

        // 저장 버튼
        document.getElementById('btn-save-agent')?.addEventListener('click', () => this.saveAgent());

        // 이미지 선택 미리보기
        document.getElementById('agent-image')?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    const preview = document.getElementById('agent-image-preview');
                    preview.innerHTML = `<img src="${ev.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // 금액 입력 필드에 콤마 자동 포맷 (설계사 모달용)
        ['agent-prev-avg', 'agent-best-premium'].forEach(id => {
            const input = document.getElementById(id);
            if (input && !input.dataset.moneyBound) {
                input.dataset.moneyBound = 'true';

                // 포커스 시 콤마 제거
                input.addEventListener('focus', (e) => {
                    let value = e.target.value.replace(/[^\d]/g, '');
                    e.target.value = value;
                });

                // 포커스 해제 시 콤마 추가
                input.addEventListener('blur', (e) => {
                    let value = e.target.value.replace(/[^\d]/g, '');
                    if (value && Number(value) > 0) {
                        e.target.value = Number(value).toLocaleString('ko-KR');
                    } else {
                        e.target.value = '';
                    }
                });
            }
        });
    },

    resetImagePreview() {
        const preview = document.getElementById('agent-image-preview');
        if (preview) {
            preview.innerHTML = '<span style="color: var(--text-muted); font-size: 2rem;">👤</span>';
        }
        const imageInput = document.getElementById('agent-image');
        if (imageInput) imageInput.value = '';
    },

    searchAgents() {
        const search = document.getElementById('search-input')?.value || '';
        const teamId = document.getElementById('filter-team')?.value || '';
        const status = document.getElementById('filter-status')?.value;

        let filtered = this.agents;

        if (search) {
            const searchLower = search.toLowerCase();
            filtered = filtered.filter(a =>
                a.name.toLowerCase().includes(searchLower)
            );
        }

        if (teamId) {
            filtered = filtered.filter(a => a.team_id == teamId);
        }

        if (status !== '') {
            filtered = filtered.filter(a => a.is_active == status);
        }

        this.renderAgents(filtered);
    },

    editAgent(id) {
        const agent = this.agents.find(a => a.id == id);
        if (!agent) return;

        document.getElementById('modal-title').textContent = '설계사 수정';
        document.getElementById('agent-id').value = agent.id;
        document.getElementById('agent-name').value = agent.name;
        document.getElementById('agent-team').value = agent.team_id || '';
        document.getElementById('agent-position').value = agent.position || 'FC';
        document.getElementById('agent-join-date').value = agent.join_date || '';
        document.getElementById('agent-prev-avg').value = this.formatNumber(agent.prev_quarter_avg || 0);
        document.getElementById('agent-best-premium').value = this.formatNumber(agent.best_monthly_premium || 0);
        document.getElementById('agent-best-count').value = agent.best_monthly_count || 0;
        document.getElementById('agent-active').checked = agent.is_active == 1;

        // 이미지 미리보기
        const preview = document.getElementById('agent-image-preview');
        if (agent.profile_image) {
            preview.innerHTML = `<img src="/229/uploads/profiles/${agent.profile_image}" style="width: 100%; height: 100%; object-fit: cover;">`;
        } else {
            this.resetImagePreview();
        }

        this.openModal('agent-modal');
    },

    async saveAgent() {
        const id = document.getElementById('agent-id').value;
        const data = {
            name: document.getElementById('agent-name').value,
            team_id: document.getElementById('agent-team').value || null,
            position: document.getElementById('agent-position').value,
            join_date: document.getElementById('agent-join-date').value || null,
            prev_quarter_avg: this.parseNumber(document.getElementById('agent-prev-avg').value),
            best_monthly_premium: this.parseNumber(document.getElementById('agent-best-premium').value),
            best_monthly_count: parseInt(document.getElementById('agent-best-count').value) || 0,
            is_active: document.getElementById('agent-active').checked ? 1 : 0
        };

        if (!data.name) {
            this.showToast('이름을 입력해주세요.', 'error');
            return;
        }

        let result;
        let agentId = id;
        if (id) {
            data.id = parseInt(id);
            result = await this.fetchAPI('/agents/update.php', {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            result = await this.fetchAPI('/agents/create.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (result.success && result.data) {
                agentId = result.data.id;
            }
        }

        if (result.success) {
            // 이미지 업로드
            const imageFile = document.getElementById('agent-image')?.files[0];
            if (imageFile && agentId) {
                await this.uploadAgentImage(agentId, imageFile);
            }

            this.showToast(result.message, 'success');
            this.closeModal();
            this.loadAgents();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    async uploadAgentImage(agentId, file) {
        const formData = new FormData();
        formData.append('agent_id', agentId);
        formData.append('image', file);

        try {
            const response = await fetch(this.apiBase + '/agents/upload-image.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                this.showToast('이미지 업로드 실패: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Image upload error:', error);
        }
    },

    async deleteAgent(id) {
        if (!confirm('정말 이 설계사를 삭제하시겠습니까?\n관련된 실적 데이터도 함께 삭제됩니다.')) return;

        const result = await this.fetchAPI('/agents/delete.php', {
            method: 'POST',
            body: JSON.stringify({ id, hard: true })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadAgents();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    // ============================================
    // 팀 관리
    // ============================================

    bindTeamEvents() {
        // 팀 관리 버튼
        document.getElementById('btn-manage-teams')?.addEventListener('click', () => {
            this.openModal('teams-modal');
            this.loadTeams();
        });

        // 팀 추가 버튼
        document.getElementById('btn-add-team')?.addEventListener('click', () => this.addTeam());

        // 팀명 입력 엔터키
        document.getElementById('new-team-name')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.addTeam();
        });
    },

    async loadTeams() {
        const result = await this.fetchAPI('/teams/list.php');
        if (result.success) {
            this.teams = result.data.teams || [];
            this.renderTeamsList();
        }
    },

    renderTeamsList() {
        const container = document.getElementById('teams-list');
        if (!container) return;

        if (this.teams.length === 0) {
            container.innerHTML = '<div class="empty-state">등록된 팀이 없습니다.</div>';
            return;
        }

        let html = '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';
        this.teams.forEach(team => {
            html += `
                <div class="team-item" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 0.5rem;">
                    <input type="text" class="form-control team-name-input" data-team-id="${team.id}" value="${team.name}" style="flex: 1;">
                    <button class="btn btn-secondary btn-sm" onclick="Admin.updateTeam(${team.id})">저장</button>
                    <button class="btn btn-secondary btn-sm" onclick="Admin.deleteTeam(${team.id})" style="color: var(--error);">삭제</button>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    },

    async addTeam() {
        const input = document.getElementById('new-team-name');
        const name = input?.value.trim();

        if (!name) {
            this.showToast('팀명을 입력해주세요.', 'error');
            return;
        }

        const result = await this.fetchAPI('/teams/create.php', {
            method: 'POST',
            body: JSON.stringify({ name })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            input.value = '';
            this.loadTeams();
            this.loadAgents(); // 팀 목록 업데이트
        } else {
            this.showToast(result.message, 'error');
        }
    },

    async updateTeam(id) {
        const input = document.querySelector(`.team-name-input[data-team-id="${id}"]`);
        const name = input?.value.trim();

        if (!name) {
            this.showToast('팀명을 입력해주세요.', 'error');
            return;
        }

        const result = await this.fetchAPI('/teams/update.php', {
            method: 'PUT',
            body: JSON.stringify({ id, name })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadAgents(); // 팀 목록 업데이트
        } else {
            this.showToast(result.message, 'error');
        }
    },

    async deleteTeam(id) {
        if (!confirm('정말 이 팀을 삭제하시겠습니까?')) return;

        const result = await this.fetchAPI('/teams/delete.php', {
            method: 'POST',
            body: JSON.stringify({ id })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadTeams();
            this.loadAgents();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    // ============================================
    // 실적 관리
    // ============================================

    performanceData: [],

    async loadPerformance() {
        // 설계사 목록 로드
        const agentsResult = await this.fetchAPI('/agents/list.php');
        if (agentsResult.success) {
            this.agents = agentsResult.data.agents || [];
            this.teams = agentsResult.data.teams || [];
        }

        // 실적 조회
        this.filterPerformance();
    },

    async filterPerformance() {
        const date = document.getElementById('filter-date')?.value || '';
        const agentId = document.getElementById('filter-agent')?.value || '';

        let params = [];
        if (date) params.push(`date=${date}`);
        if (agentId) params.push(`agent_id=${agentId}`);

        const result = await this.fetchAPI(`/performance/list.php?${params.join('&')}`);

        if (result.success) {
            this.performanceData = result.data.performances || [];
            this.renderInlinePerformance(date);
            this.updatePerformanceSummary(result.data);
        }
    },

    // 엑셀형 인라인 실적 입력 UI
    renderInlinePerformance(date) {
        const tbody = document.getElementById('performance-tbody');
        if (!tbody) return;

        // 활성 설계사 목록 + 해당 날짜의 실적
        const activeAgents = this.agents.filter(a => a.is_active == 1);

        if (activeAgents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">활성화된 설계사가 없습니다.</td></tr>';
            return;
        }

        // 실적 데이터를 agent_id로 매핑
        const perfMap = {};
        this.performanceData.forEach(p => {
            if (p.performance_date === date) {
                perfMap[p.agent_id] = p;
            }
        });

        let html = '';
        activeAgents.forEach(agent => {
            const perf = perfMap[agent.id] || {};
            const earlyValue = perf.early_premium ? this.formatNumber(perf.early_premium) : '';
            const monthlyValue = perf.monthly_premium ? this.formatNumber(perf.monthly_premium) : '';
            const countValue = perf.contract_count || '';

            html += `
                <tr data-agent-id="${agent.id}" data-perf-id="${perf.id || ''}">
                    <td style="text-align: left;">
                        <div class="agent-cell">
                            <div class="agent-profile">
                                ${agent.profile_image
                                    ? `<img src="/229/uploads/profiles/${agent.profile_image}" alt="${agent.name}">`
                                    : '<span class="agent-profile-placeholder">👤</span>'}
                            </div>
                            <div>
                                <div class="agent-name">${agent.name}</div>
                                <div class="agent-team">${agent.team_name || ''}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control perf-input money-input" data-field="early_premium" value="${earlyValue}" placeholder="0" style="text-align: right; width: 120px;">
                    </td>
                    <td>
                        <input type="text" class="form-control perf-input money-input" data-field="monthly_premium" value="${monthlyValue}" placeholder="0" style="text-align: right; width: 120px;">
                    </td>
                    <td>
                        <input type="number" class="form-control perf-input" data-field="contract_count" value="${countValue}" placeholder="0" min="0" style="text-align: right; width: 80px;">
                    </td>
                    <td>
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <button class="btn btn-primary btn-sm" onclick="Admin.saveInlinePerformance(${agent.id})">저장</button>
                            <button class="btn btn-secondary btn-sm" onclick="Admin.openDetailModal(${agent.id})">상세</button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        // 금액 입력 필드에 콤마 자동 포맷 바인딩
        this.bindMoneyInputs();
    },

    async saveInlinePerformance(agentId) {
        const row = document.querySelector(`tr[data-agent-id="${agentId}"]`);
        if (!row) return;

        const date = document.getElementById('filter-date')?.value;
        if (!date) {
            this.showToast('날짜를 선택해주세요.', 'error');
            return;
        }

        const perfId = row.dataset.perfId;
        const earlyInput = row.querySelector('[data-field="early_premium"]');
        const monthlyInput = row.querySelector('[data-field="monthly_premium"]');
        const countInput = row.querySelector('[data-field="contract_count"]');

        const data = {
            agent_id: agentId,
            performance_date: date,
            early_premium: this.parseNumber(earlyInput?.value),
            monthly_premium: this.parseNumber(monthlyInput?.value),
            contract_count: parseInt(countInput?.value) || 0
        };

        // 7일 자동 분류: 매월 7일까지는 조기가동, 그 이후는 월납보험료
        const day = parseInt(date.split('-')[2], 10);
        if (day <= 7) {
            // 7일까지 입력된 금액은 조기가동으로
            // (이미 입력된 값 그대로 사용)
        } else {
            // 7일 이후에 입력된 금액 중 조기가동에 입력된 것은 월납으로 이동
            if (data.early_premium > 0 && data.monthly_premium === 0) {
                data.monthly_premium = data.early_premium;
                data.early_premium = 0;
            }
        }

        let result;
        if (perfId) {
            data.id = parseInt(perfId);
            result = await this.fetchAPI('/performance/update.php', {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            result = await this.fetchAPI('/performance/create.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }

        if (result.success) {
            this.showToast(result.message, 'success');
            this.filterPerformance();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    updatePerformanceSummary(data) {
        document.getElementById('summary-count').textContent = data.count || 0;
        document.getElementById('summary-early').textContent = this.formatNumber(data.totals?.early_premium);
        document.getElementById('summary-monthly').textContent = this.formatNumber(data.totals?.monthly_premium);
        document.getElementById('summary-contracts').textContent = data.totals?.contract_count || 0;
    },

    bindPerformanceEvents() {
        // 조회 버튼
        document.getElementById('btn-filter')?.addEventListener('click', () => this.filterPerformance());

        // 오늘 버튼
        document.getElementById('btn-today')?.addEventListener('click', () => {
            document.getElementById('filter-date').value = new Date().toISOString().split('T')[0];
            this.filterPerformance();
        });

        // 날짜 변경 시 자동 조회
        document.getElementById('filter-date')?.addEventListener('change', () => {
            this.filterPerformance();
        });
    },

    async deletePerformance(id) {
        if (!confirm('정말 이 실적을 삭제하시겠습니까?')) return;

        const result = await this.fetchAPI('/performance/delete.php', {
            method: 'POST',
            body: JSON.stringify({ id })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.filterPerformance();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    // ============================================
    // 실적 상세 모달
    // ============================================

    openDetailModal(agentId) {
        this.currentDetailAgentId = agentId;
        this.openModal('detail-modal');
        this.loadAgentDetail(agentId);

        // 오늘 날짜 기본 설정
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('detail-add-date').value = today;

        // 추가 버튼 이벤트
        const addBtn = document.getElementById('btn-detail-add');
        if (addBtn) {
            addBtn.onclick = () => this.addDetailPerformance();
        }

        // 금액 입력 필드 바인딩
        this.bindMoneyInputs();
    },

    closeDetailModal() {
        document.getElementById('detail-modal')?.classList.remove('active');
        this.currentDetailAgentId = null;
    },

    async loadAgentDetail(agentId) {
        const result = await this.fetchAPI(`/performance/agent-detail.php?agent_id=${agentId}`);

        if (result.success) {
            const { agent, quarter, performances, totals } = result.data;

            // 설계사 정보 표시
            document.getElementById('detail-agent-name').textContent = agent.name;
            document.getElementById('detail-agent-info').textContent =
                `${agent.name} ${agent.position || ''} (${agent.team_name || '팀 없음'})`;
            document.getElementById('detail-quarter-info').textContent =
                quarter ? `${quarter.year}년 ${quarter.quarter}분기` : '';

            // 프로필 이미지
            const profileEl = document.getElementById('detail-agent-profile');
            if (agent.profile_image) {
                profileEl.innerHTML = `<img src="/229/uploads/profiles/${agent.profile_image}" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else {
                profileEl.innerHTML = '<span class="agent-profile-placeholder">👤</span>';
            }

            // 합계 표시
            document.getElementById('detail-total-early').textContent = totals.early_formatted;
            document.getElementById('detail-total-monthly').textContent = totals.monthly_formatted;
            document.getElementById('detail-total-count').textContent = totals.count;

            // 실적 내역 표시
            this.renderDetailPerformances(performances);
        } else {
            this.showToast(result.message, 'error');
        }
    },

    renderDetailPerformances(performances) {
        const tbody = document.getElementById('detail-performance-tbody');
        if (!tbody) return;

        if (!performances || performances.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">등록된 실적이 없습니다.</td></tr>';
            return;
        }

        let html = '';
        performances.forEach(p => {
            const dayBadge = p.is_early
                ? '<span class="badge badge-gold">조기</span>'
                : '<span class="badge">일반</span>';

            html += `
                <tr data-perf-id="${p.id}">
                    <td style="text-align: left;">${p.performance_date}</td>
                    <td>${dayBadge}</td>
                    <td>${p.early_premium_formatted}</td>
                    <td>${p.monthly_premium_formatted}</td>
                    <td>${p.contract_count}</td>
                    <td>
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <button class="btn btn-secondary btn-sm" onclick="Admin.editDetailPerformance(${p.id})">수정</button>
                            <button class="btn btn-secondary btn-sm" onclick="Admin.deleteDetailPerformance(${p.id})" style="color: var(--error);">삭제</button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    async addDetailPerformance() {
        const date = document.getElementById('detail-add-date')?.value;
        if (!date) {
            this.showToast('날짜를 선택해주세요.', 'error');
            return;
        }

        const earlyValue = this.parseNumber(document.getElementById('detail-add-early')?.value);
        const monthlyValue = this.parseNumber(document.getElementById('detail-add-monthly')?.value);
        const countValue = parseInt(document.getElementById('detail-add-count')?.value) || 0;

        if (earlyValue === 0 && monthlyValue === 0 && countValue === 0) {
            this.showToast('실적을 입력해주세요.', 'error');
            return;
        }

        // 7일 자동 분류
        const day = parseInt(date.split('-')[2], 10);
        let early = earlyValue;
        let monthly = monthlyValue;

        if (day > 7) {
            // 7일 이후에 입력된 조기가동 금액은 월납으로 이동
            if (early > 0 && monthly === 0) {
                monthly = early;
                early = 0;
            }
        }

        const data = {
            agent_id: this.currentDetailAgentId,
            performance_date: date,
            early_premium: early,
            monthly_premium: monthly,
            contract_count: countValue
        };

        const result = await this.fetchAPI('/performance/create.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            // 입력 필드 초기화
            document.getElementById('detail-add-early').value = '';
            document.getElementById('detail-add-monthly').value = '';
            document.getElementById('detail-add-count').value = '0';
            // 상세 데이터 새로고침
            this.loadAgentDetail(this.currentDetailAgentId);
            // 메인 테이블도 새로고침
            this.filterPerformance();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    async editDetailPerformance(perfId) {
        // 간단한 프롬프트로 수정 (추후 인라인 편집으로 개선 가능)
        const row = document.querySelector(`tr[data-perf-id="${perfId}"]`);
        if (!row) return;

        const cells = row.querySelectorAll('td');
        const currentDate = cells[0].textContent.trim();
        const currentEarly = cells[2].textContent.replace(/,/g, '');
        const currentMonthly = cells[3].textContent.replace(/,/g, '');
        const currentCount = cells[4].textContent;

        const newEarly = prompt('조기가동 금액:', currentEarly);
        if (newEarly === null) return;

        const newMonthly = prompt('월납보험료:', currentMonthly);
        if (newMonthly === null) return;

        const newCount = prompt('건수:', currentCount);
        if (newCount === null) return;

        const data = {
            id: perfId,
            early_premium: parseInt(newEarly) || 0,
            monthly_premium: parseInt(newMonthly) || 0,
            contract_count: parseInt(newCount) || 0
        };

        const result = await this.fetchAPI('/performance/update.php', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadAgentDetail(this.currentDetailAgentId);
            this.filterPerformance();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    async deleteDetailPerformance(perfId) {
        if (!confirm('정말 이 실적을 삭제하시겠습니까?')) return;

        const result = await this.fetchAPI('/performance/delete.php', {
            method: 'POST',
            body: JSON.stringify({ id: perfId })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadAgentDetail(this.currentDetailAgentId);
            this.filterPerformance();
        } else {
            this.showToast(result.message, 'error');
        }
    },

    // ============================================
    // 탭 관리
    // ============================================

    bindTabEvents() {
        const tabs = document.querySelectorAll('.tabs .tab');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;

                // 탭 활성화
                document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // 콘텐츠 전환
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                const targetContent = document.getElementById(`tab-${tabName}`);
                if (targetContent) {
                    targetContent.style.display = 'block';
                }

                // 근태 탭 선택 시 데이터 로드
                if (tabName === 'attendance') {
                    Admin.loadAttendance();
                }
            });
        });
    },

    // ============================================
    // 근태 관리 (월 1회 미출근/출근/만근 선택)
    // ============================================

    attendanceData: {},

    async loadAttendance() {
        const date = document.getElementById('attendance-date')?.value || new Date().toISOString().split('T')[0];
        // 월 기준으로 조회 (해당 월의 첫째 날)
        const monthDate = date.substring(0, 7) + '-01';

        const result = await this.fetchAPI(`/performance/attendance.php?date=${monthDate}`);

        if (result.success) {
            this.renderAttendanceGrid(result.data.agents, monthDate);
            this.renderAttendanceSummary(result.data.summary);
        } else {
            const grid = document.getElementById('attendance-grid');
            if (grid) {
                grid.innerHTML = `<div class="empty-state">데이터를 불러올 수 없습니다.</div>`;
            }
        }
    },

    renderAttendanceGrid(agents, date) {
        const grid = document.getElementById('attendance-grid');
        if (!grid) return;

        if (!agents || agents.length === 0) {
            grid.innerHTML = '<div class="empty-state">등록된 설계사가 없습니다.</div>';
            return;
        }

        this.attendanceData = {};

        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">';
        agents.forEach(agent => {
            const status = agent.attendance_status || '';
            this.attendanceData[agent.id] = status;

            const profileImg = agent.profile_image
                ? `<img src="/229/uploads/profiles/${agent.profile_image}" alt="${agent.name}" style="width: 100%; height: 100%; object-fit: cover;">`
                : '<span style="color: var(--text-muted);">👤</span>';

            html += `
                <div class="attendance-card-new" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-tertiary); border-radius: 0.75rem; border: 1px solid var(--border-color);">
                    <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: var(--bg-secondary); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        ${profileImg}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600;">${agent.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${agent.team_name || agent.position || ''}</div>
                    </div>
                    <select class="form-control attendance-select" data-agent-id="${agent.id}" style="width: 100px;">
                        <option value="" ${!status ? 'selected' : ''}>선택</option>
                        <option value="absent" ${status === 'absent' ? 'selected' : ''}>미출근 (0점)</option>
                        <option value="partial" ${status === 'partial' ? 'selected' : ''}>출근 (10점)</option>
                        <option value="present" ${status === 'present' ? 'selected' : ''}>만근 (20점)</option>
                    </select>
                </div>
            `;
        });
        html += '</div>';

        grid.innerHTML = html;

        // 드롭다운 변경 이벤트
        grid.querySelectorAll('.attendance-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const agentId = e.target.dataset.agentId;
                this.attendanceData[agentId] = e.target.value;
            });
        });
    },

    renderAttendanceSummary(summary) {
        const tbody = document.getElementById('attendance-summary-tbody');
        if (!tbody) return;

        if (!summary || summary.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-state">근태 기록이 없습니다.</td></tr>';
            return;
        }

        let html = '';
        summary.forEach(agent => {
            const attendanceScore = agent.attendance_score || 0;
            let badgeText = '미입력';
            let badgeClass = '';

            if (attendanceScore >= 20) {
                badgeText = '만근';
                badgeClass = 'badge-gold';
            } else if (attendanceScore >= 10) {
                badgeText = '출근';
                badgeClass = 'badge-success';
            } else if (attendanceScore > 0 || agent.attendance_count > 0) {
                badgeText = '미출근';
                badgeClass = 'badge-error';
            }

            html += `
                <tr>
                    <td style="text-align: left;">
                        <div class="agent-cell">
                            <div class="agent-profile">
                                ${agent.profile_image
                                    ? `<img src="/229/uploads/profiles/${agent.profile_image}" alt="${agent.name}">`
                                    : '<span class="agent-profile-placeholder">👤</span>'}
                            </div>
                            <div class="agent-info">
                                <div class="agent-name">${agent.name}</div>
                                <div class="agent-team">${agent.team_name || ''}</div>
                            </div>
                        </div>
                    </td>
                    <td>-</td>
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                    <td class="${attendanceScore >= 20 ? 'text-gold font-bold' : ''}">${attendanceScore}점</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    },

    bindAttendanceEvents() {
        // 오늘 버튼
        document.getElementById('btn-attendance-today')?.addEventListener('click', () => {
            document.getElementById('attendance-date').value = new Date().toISOString().split('T')[0];
            Admin.loadAttendance();
        });

        // 날짜 변경
        document.getElementById('attendance-date')?.addEventListener('change', () => {
            Admin.loadAttendance();
        });

        // 저장 버튼
        document.getElementById('btn-save-all-attendance')?.addEventListener('click', () => Admin.saveAllAttendance());

        // 탭 이벤트 바인딩
        Admin.bindTabEvents();
    },

    async saveAllAttendance() {
        const date = document.getElementById('attendance-date')?.value;
        if (!date) {
            this.showToast('날짜를 선택해주세요.', 'error');
            return;
        }

        // 월 기준 날짜 (해당 월의 첫째 날)
        const monthDate = date.substring(0, 7) + '-01';

        // 선택된 근태만 저장
        const attendanceList = [];
        for (const [agentId, status] of Object.entries(this.attendanceData)) {
            if (status) {
                attendanceList.push({
                    agent_id: parseInt(agentId),
                    status: status
                });
            }
        }

        if (attendanceList.length === 0) {
            this.showToast('저장할 근태 데이터가 없습니다.', 'error');
            return;
        }

        const result = await this.fetchAPI('/performance/attendance.php', {
            method: 'POST',
            body: JSON.stringify({
                date: monthDate,
                attendance: attendanceList
            })
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            this.loadAttendance();
        } else {
            this.showToast(result.message, 'error');
        }
    }
};
