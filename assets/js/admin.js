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

        // 모달 오버레이 클릭 이벤트 바인딩
        this.bindModalOverlayClose();

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

            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError, 'Response:', text);
                return { success: false, message: '서버 응답 오류' };
            }
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

    // 모달 콘텐츠 로드
    async loadModalContent() {
        try {
            const result = await this.fetchAPI('/settings/modal.php');
            if (result.success) {
                const eventTextarea = document.getElementById('modal-event-content');
                const awardTextarea = document.getElementById('modal-award-content');

                if (eventTextarea) {
                    eventTextarea.value = result.data.event || '';
                }
                if (awardTextarea) {
                    awardTextarea.value = result.data.award || '';
                }
            }
        } catch (error) {
            console.error('Modal content load error:', error);
        }
    },

    // 모달 콘텐츠 저장
    async saveModalContent(type) {
        const textareaId = type === 'event' ? 'modal-event-content' : 'modal-award-content';
        const textarea = document.getElementById(textareaId);

        if (!textarea) return;

        try {
            const result = await this.fetchAPI('/settings/modal.php', {
                method: 'POST',
                body: JSON.stringify({
                    type: type,
                    content: textarea.value
                })
            });

            if (result.success) {
                this.showToast('저장되었습니다.', 'success');
            } else {
                this.showToast(result.message || '저장에 실패했습니다.', 'error');
            }
        } catch (error) {
            console.error('Modal content save error:', error);
            this.showToast('저장에 실패했습니다.', 'error');
        }
    },

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

    // 모달 오버레이 클릭 시 닫기 바인딩
    bindModalOverlayClose() {
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            if (overlay.dataset.overlayBound) return;
            overlay.dataset.overlayBound = 'true';

            overlay.addEventListener('click', (e) => {
                // 오버레이 자체를 클릭했을 때만 닫기 (모달 내부 클릭 시 무시)
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
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

    // 날짜 입력 필드 자동 포맷 (YYYY-MM-DD)
    bindDateInputs() {
        document.querySelectorAll('.date-input').forEach(input => {
            if (input.dataset.dateBound) return;
            input.dataset.dateBound = 'true';

            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/[^\d]/g, '');

                // 자동으로 하이픈 추가
                if (value.length > 4) {
                    value = value.substring(0, 4) + '-' + value.substring(4);
                }
                if (value.length > 7) {
                    value = value.substring(0, 7) + '-' + value.substring(7);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }

                e.target.value = value;
            });

            // 키보드 입력 시 숫자만 허용
            input.addEventListener('keypress', (e) => {
                if (!/[\d]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                    e.preventDefault();
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
                parseFloat(stats.data.today.today_monthly) || 0
            );
            document.getElementById('stat-today-contracts').textContent = stats.data.today.today_contracts || '0';
        }

        // 최근 실적 로드
        const performances = await this.fetchAPI('/performance/list.php?limit=10');
        if (performances.success) {
            this.renderRecentPerformance(performances.data.performances);
        }

        // 분기 목록 로드
        this.loadQuarters();
    },

    // 분기 목록 로드
    async loadQuarters() {
        const result = await this.fetchAPI('/dashboard/quarters.php');
        if (result.success && result.data.quarters) {
            const select = document.getElementById('quarter-select');
            if (select) {
                select.innerHTML = result.data.quarters.map(q =>
                    `<option value="${q.id}" data-target="${q.target_score || 200}">${q.year}년 ${q.quarter}분기</option>`
                ).join('');

                // 현재 분기 선택
                if (result.data.current) {
                    select.value = result.data.current.id;
                }

                // 목표 점수 표시
                this.updateTargetScoreDisplay();

                // 분기 변경 시 목표 점수 업데이트
                select.addEventListener('change', () => this.updateTargetScoreDisplay());
            }
        }
    },

    // 목표 점수 표시 업데이트
    updateTargetScoreDisplay() {
        const select = document.getElementById('quarter-select');
        const targetInput = document.getElementById('target-score');
        if (select && targetInput) {
            const selectedOption = select.options[select.selectedIndex];
            targetInput.value = selectedOption?.dataset.target || 200;
        }
    },

    // 목표 점수 저장
    async saveTargetScore() {
        const quarterId = document.getElementById('quarter-select')?.value;
        const targetScore = document.getElementById('target-score')?.value;

        if (!quarterId) {
            this.showToast('분기를 선택해주세요.', 'error');
            return;
        }

        const result = await this.fetchAPI('/settings/target-score.php', {
            method: 'POST',
            body: JSON.stringify({
                quarter_id: parseInt(quarterId),
                target_score: parseInt(targetScore) || 200
            })
        });

        if (result.success) {
            this.showToast('목표 점수가 저장되었습니다.', 'success');
            // 분기 목록 다시 로드
            this.loadQuarters();
        } else {
            this.showToast(result.message || '저장에 실패했습니다.', 'error');
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

        // 날짜 입력 필드 자동 포맷 바인딩
        this.bindDateInputs();
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

        // 모두 저장 버튼
        document.getElementById('btn-save-all-teams')?.addEventListener('click', () => this.saveAllTeams());
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

    async saveAllTeams() {
        const inputs = document.querySelectorAll('.team-name-input');
        if (inputs.length === 0) {
            this.showToast('저장할 팀이 없습니다.', 'error');
            return;
        }

        // 변경된 팀 목록 수집
        const teamsToUpdate = [];
        inputs.forEach(input => {
            const teamId = parseInt(input.dataset.teamId);
            const newName = input.value.trim();
            const originalTeam = this.teams.find(t => t.id === teamId);

            if (newName && originalTeam && originalTeam.name !== newName) {
                teamsToUpdate.push({ id: teamId, name: newName });
            }
        });

        if (teamsToUpdate.length === 0) {
            this.showToast('변경된 내용이 없습니다.', 'info');
            return;
        }

        // 모든 팀 업데이트
        let successCount = 0;
        let failCount = 0;

        for (const team of teamsToUpdate) {
            const result = await this.fetchAPI('/teams/update.php', {
                method: 'PUT',
                body: JSON.stringify(team)
            });

            if (result.success) {
                successCount++;
            } else {
                failCount++;
            }
        }

        if (failCount === 0) {
            this.showToast(`${successCount}개 팀이 저장되었습니다.`, 'success');
        } else {
            this.showToast(`${successCount}개 성공, ${failCount}개 실패`, 'error');
        }

        this.loadTeams();
        this.loadAgents();
    },

    // ============================================
    // 실적 관리
    // ============================================

    performanceData: [],
    agentMonthlyStats: {},
    agentThreeW: {},

    async loadPerformance() {
        // 설계사 목록 로드
        const agentsResult = await this.fetchAPI('/agents/list.php');
        if (agentsResult.success) {
            this.agents = agentsResult.data.agents || [];
            this.teams = agentsResult.data.teams || [];
            this.populatePerfTeamFilter();
        }

        // 실적 조회
        this.filterPerformance();
    },

    // 실적 입력 페이지 팀 필터 채우기
    populatePerfTeamFilter() {
        const select = document.getElementById('filter-perf-team');
        if (!select) return;

        // 기존 옵션 유지 (전체 팀)
        const firstOption = select.querySelector('option');
        select.innerHTML = '';
        if (firstOption) select.appendChild(firstOption);

        this.teams.forEach(team => {
            const option = document.createElement('option');
            option.value = team.id;
            option.textContent = team.name;
            select.appendChild(option);
        });
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
            this.agentMonthlyStats = result.data.agent_monthly_stats || {};
            this.agentThreeW = result.data.agent_three_w || {};
            this.renderInlinePerformance(date);
            this.updatePerformanceSummary(result.data);
        }
    },

    // 엑셀형 인라인 실적 입력 UI
    renderInlinePerformance(date) {
        const tbody = document.getElementById('performance-tbody');
        if (!tbody) return;

        // 팀 필터 값 가져오기
        const teamFilter = document.getElementById('filter-perf-team')?.value || '';

        // 활성 설계사 목록 필터링
        let activeAgents = this.agents.filter(a => a.is_active == 1);

        // 팀 필터 적용
        if (teamFilter) {
            activeAgents = activeAgents.filter(a => a.team_id == teamFilter);
        }

        // 가나다순 정렬
        activeAgents.sort((a, b) => a.name.localeCompare(b.name, 'ko'));

        if (activeAgents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state">활성화된 설계사가 없습니다.</td></tr>';
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
            const monthlyValue = perf.monthly_premium ? this.formatNumber(perf.monthly_premium) : '';
            const countValue = perf.contract_count || '';
            const eventValue = perf.event_score || '';

            // 월간 누적 통계
            const stats = this.agentMonthlyStats[agent.id] || {};
            const monthlyTotal = this.formatNumber(stats.monthly_total || 0);
            const monthlyEarly = this.formatNumber(stats.monthly_early || 0);
            const monthlyCount = stats.monthly_count || 0;

            // 3W 주차
            const threeW = this.agentThreeW[agent.id] || 0;

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
                        <input type="text" class="form-control perf-input money-input" data-field="monthly_premium" value="${monthlyValue}" placeholder="0" style="text-align: right; width: 120px;">
                    </td>
                    <td>
                        <input type="number" class="form-control perf-input" data-field="contract_count" value="${countValue}" placeholder="0" min="0" style="text-align: right; width: 60px;">
                    </td>
                    <td>
                        <input type="number" class="form-control perf-input" data-field="event_score" value="${eventValue}" placeholder="0" min="0" step="0.1" style="text-align: right; width: 60px;">
                    </td>
                    <td style="text-align: right; color: var(--gold);">${monthlyTotal}</td>
                    <td style="text-align: right;">${monthlyEarly}</td>
                    <td style="text-align: center;">${monthlyCount}</td>
                    <td style="text-align: center; font-weight: 600; white-space: nowrap; color: ${threeW > 0 ? 'var(--gold)' : 'var(--text-muted)'};">${threeW}주</td>
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
        const monthlyInput = row.querySelector('[data-field="monthly_premium"]');
        const countInput = row.querySelector('[data-field="contract_count"]');
        const eventInput = row.querySelector('[data-field="event_score"]');

        const data = {
            agent_id: agentId,
            performance_date: date,
            monthly_premium: this.parseNumber(monthlyInput?.value),
            contract_count: parseInt(countInput?.value) || 0,
            event_score: parseFloat(eventInput?.value) || 0
        };
        // 조기가동은 서버에서 날짜 기반으로 자동 계산됨

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
        // 입력 건수는 선택된 날짜 기준
        document.getElementById('summary-count').textContent = data.count || 0;

        // 합계는 월 전체 기준 (monthly_totals 사용)
        const totals = data.monthly_totals || data.totals || {};
        document.getElementById('summary-early').textContent = this.formatNumber(totals.early_premium);
        document.getElementById('summary-monthly').textContent = this.formatNumber(totals.monthly_premium);
        document.getElementById('summary-contracts').textContent = totals.contract_count || 0;
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

        // 팀 필터 변경 시 목록 다시 렌더링
        document.getElementById('filter-perf-team')?.addEventListener('change', () => {
            const date = document.getElementById('filter-date')?.value || '';
            this.renderInlinePerformance(date);
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
            const { agent, quarter, performances, totals, monthly_totals } = result.data;

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

            // 합계 표시 (이번 달 기준)
            document.getElementById('detail-total-early').textContent = monthly_totals.early_formatted;
            document.getElementById('detail-total-monthly').textContent = monthly_totals.monthly_formatted;
            document.getElementById('detail-total-count').textContent = monthly_totals.count;

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
                <tr data-perf-id="${p.id}" data-monthly="${p.monthly_premium}" data-count="${p.contract_count}">
                    <td style="text-align: left;">${p.performance_date}</td>
                    <td>${dayBadge}</td>
                    <td class="cell-early">${p.early_premium_formatted}</td>
                    <td class="cell-monthly">${p.monthly_premium_formatted}</td>
                    <td class="cell-count">${p.contract_count}</td>
                    <td class="cell-actions">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <button class="btn btn-secondary btn-sm btn-edit" onclick="Admin.startEditDetailPerformance(${p.id})">수정</button>
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

        const monthlyValue = this.parseNumber(document.getElementById('detail-add-monthly')?.value);
        const countValue = parseInt(document.getElementById('detail-add-count')?.value) || 0;

        if (monthlyValue === 0 && countValue === 0) {
            this.showToast('실적을 입력해주세요.', 'error');
            return;
        }

        const data = {
            agent_id: this.currentDetailAgentId,
            performance_date: date,
            monthly_premium: monthlyValue,
            contract_count: countValue
        };
        // 조기가동은 서버에서 날짜 기반으로 자동 계산됨

        const result = await this.fetchAPI('/performance/create.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (result.success) {
            this.showToast(result.message, 'success');
            // 입력 필드 초기화
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

    // 인라인 편집 시작
    startEditDetailPerformance(perfId) {
        const row = document.querySelector(`tr[data-perf-id="${perfId}"]`);
        if (!row) return;

        // 이미 편집 중인지 확인
        if (row.classList.contains('editing')) return;
        row.classList.add('editing');

        const currentMonthly = row.dataset.monthly || 0;
        const currentCount = row.dataset.count || 0;

        const cellMonthly = row.querySelector('.cell-monthly');
        const cellCount = row.querySelector('.cell-count');
        const cellActions = row.querySelector('.cell-actions');

        // 입력 필드로 변경
        cellMonthly.innerHTML = `<input type="text" class="form-control money-input edit-monthly" value="${this.formatNumber(currentMonthly)}" style="width: 100px; text-align: right;">`;
        cellCount.innerHTML = `<input type="number" class="form-control edit-count" value="${currentCount}" min="0" style="width: 60px; text-align: right;">`;

        // 버튼 변경
        cellActions.innerHTML = `
            <div style="display: flex; gap: 4px; justify-content: center;">
                <button class="btn btn-primary btn-sm" onclick="Admin.saveEditDetailPerformance(${perfId})">저장</button>
                <button class="btn btn-secondary btn-sm" onclick="Admin.cancelEditDetailPerformance(${perfId})">취소</button>
            </div>
        `;

        // 금액 입력 필드 바인딩
        this.bindMoneyInputs();

        // 첫 번째 입력 필드에 포커스
        const firstInput = cellMonthly.querySelector('input');
        if (firstInput) {
            firstInput.focus();
            firstInput.select();
        }
    },

    // 인라인 편집 저장
    async saveEditDetailPerformance(perfId) {
        const row = document.querySelector(`tr[data-perf-id="${perfId}"]`);
        if (!row) return;

        const monthlyInput = row.querySelector('.edit-monthly');
        const countInput = row.querySelector('.edit-count');

        const data = {
            id: perfId,
            monthly_premium: this.parseNumber(monthlyInput?.value),
            contract_count: parseInt(countInput?.value) || 0
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

    // 인라인 편집 취소
    cancelEditDetailPerformance(perfId) {
        // 데이터 새로고침으로 원래 상태 복원
        this.loadAgentDetail(this.currentDetailAgentId);
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
            // 전체 에이전트 데이터 저장
            this.allAttendanceAgents = result.data.agents;

            // 팀 필터 드롭다운 채우기
            this.populateAttendanceTeamFilter(result.data.agents);

            // 현재 필터 적용하여 렌더링
            const teamFilter = document.getElementById('filter-attendance-team')?.value || '';
            const filteredAgents = this.filterAttendanceAgents(result.data.agents, teamFilter);

            this.renderAttendanceGrid(filteredAgents, monthDate);
            this.renderAttendanceSummary(result.data.summary);
        } else {
            const grid = document.getElementById('attendance-grid');
            if (grid) {
                grid.innerHTML = `<div class="empty-state">데이터를 불러올 수 없습니다.</div>`;
            }
        }
    },

    populateAttendanceTeamFilter(agents) {
        const select = document.getElementById('filter-attendance-team');
        if (!select) return;

        // 현재 선택값 저장
        const currentValue = select.value;

        // 팀 목록 추출 (중복 제거)
        const teams = [...new Set(agents.map(a => a.team_name).filter(Boolean))].sort();

        // 옵션 생성
        select.innerHTML = '<option value="">전체 지사</option>';
        teams.forEach(team => {
            const option = document.createElement('option');
            option.value = team;
            option.textContent = team;
            select.appendChild(option);
        });

        // 이전 선택값 복원
        if (currentValue && teams.includes(currentValue)) {
            select.value = currentValue;
        }
    },

    filterAttendanceAgents(agents, teamFilter) {
        if (!teamFilter) return agents;
        return agents.filter(a => a.team_name === teamFilter);
    },

    renderAttendanceGrid(agents, date) {
        const grid = document.getElementById('attendance-grid');
        if (!grid) return;

        if (!agents || agents.length === 0) {
            grid.innerHTML = '<div class="empty-state">등록된 설계사가 없습니다.</div>';
            return;
        }

        this.attendanceData = {};

        let html = '<div class="attendance-grid-layout">';
        agents.forEach(agent => {
            const status = agent.attendance_status || '';
            this.attendanceData[agent.id] = status;

            const profileImg = agent.profile_image
                ? `<img src="/229/uploads/profiles/${agent.profile_image}" alt="${agent.name}" style="width: 100%; height: 100%; object-fit: cover;">`
                : '<span style="color: var(--text-muted); font-size: 1.5rem;">👤</span>';

            html += `
                <div class="attendance-card-new" style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; background: var(--bg-tertiary); border-radius: 0.75rem; border: 1px solid var(--border-color);">
                    <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: var(--bg-secondary); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        ${profileImg}
                    </div>
                    <div style="flex: 1; min-width: 0; font-weight: 600; font-size: 1.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${agent.name}</div>
                    <select class="form-control attendance-select" data-agent-id="${agent.id}" style="width: 90px; padding: 0.5rem; font-size: 1rem;">
                        <option value="" ${!status ? 'selected' : ''}>-</option>
                        <option value="absent" ${status === 'absent' ? 'selected' : ''}>미출근</option>
                        <option value="partial" ${status === 'partial' ? 'selected' : ''}>출근</option>
                        <option value="present" ${status === 'present' ? 'selected' : ''}>만근</option>
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

        // 지사 필터 변경
        document.getElementById('filter-attendance-team')?.addEventListener('change', () => {
            const teamFilter = document.getElementById('filter-attendance-team')?.value || '';
            const date = document.getElementById('attendance-date')?.value || new Date().toISOString().split('T')[0];
            const monthDate = date.substring(0, 7) + '-01';

            if (Admin.allAttendanceAgents) {
                const filteredAgents = Admin.filterAttendanceAgents(Admin.allAttendanceAgents, teamFilter);
                Admin.renderAttendanceGrid(filteredAgents, monthDate);
            }
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
