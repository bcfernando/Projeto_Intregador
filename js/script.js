document.addEventListener('DOMContentLoaded', () => {
  // =========================
  // Helpers de rede (robustos)
  // =========================
  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const raw = await res.text(); // lê como texto para depurar

    if (!raw || raw.trim() === '') {
      throw new Error(`Resposta vazia de ${url}`);
    }
    let json;
    try {
      json = JSON.parse(raw);
    } catch (e) {
      throw new Error(`Resposta não-JSON de ${url}: ${raw.slice(0, 300)}`);
    }

    if (!res.ok || json.success === false) {
      const msg = (json && json.message) ? json.message : `HTTP ${res.status}`;
      throw new Error(msg);
    }
    return json;
  }

  const postData = async (url, data) => {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
      });
      const raw = await res.text();
      if (!raw || raw.trim() === '') {
        throw new Error(`Resposta vazia de ${url}`);
      }
      let json;
      try { json = JSON.parse(raw); }
      catch (e) { throw new Error(`Resposta não-JSON de ${url}: ${raw.slice(0, 300)}`); }

      if (!res.ok || json.success === false) {
        const msg = (json && json.message) ? json.message : `HTTP ${res.status}`;
        throw new Error(msg);
      }
      return json;
    } catch (error) {
      console.error(`POST ${url} Error:`, error);
      return { success: false, message: error.message || 'Erro de comunicação.' };
    }
  };

  // ===============================
  // Toasts e confirmação (UI)
  // ===============================
  const showToast = (message, type = 'info') => {
    const container = document.getElementById('toast-container'); if (!container) return;
    const toast = document.createElement('div'); toast.className = `toast ${type}`;
    let icon = 'ℹ️'; if (type === 'success') icon = '✅'; if (type === 'error') icon = '❌';
    toast.innerHTML = `<span class="toast-icon">${icon}</span> <span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => { toast.classList.remove('show'); toast.addEventListener('transitionend', () => toast.remove()); }, 4000);
  };

  const showConfirm = (text) => {
    return new Promise(resolve => {
      const modal = document.getElementById('confirm-modal');
      const textEl = document.getElementById('confirm-modal-text');
      const btnYes = document.getElementById('confirm-modal-btn-yes');
      const btnNo = document.getElementById('confirm-modal-btn-no');
      if (!modal || !textEl || !btnYes || !btnNo) { resolve(confirm(text)); return; }
      textEl.textContent = text;
      modal.style.display = 'flex';
      const closeHandler = (decision) => {
        modal.style.display = 'none';
        btnYes.onclick = null;
        btnNo.onclick = null;
        resolve(decision);
      };
      btnYes.onclick = () => closeHandler(true);
      btnNo.onclick = () => closeHandler(false);
    });
  };

  // =====================================
  // Form de inativação de bombeiro
  // =====================================
  const deleteForms = document.querySelectorAll('form[action="bombeiros.php"]');
  deleteForms.forEach(form => {
    if (form.querySelector('input[name="delete_bombeiro_id"]')) {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (await showConfirm('Tem certeza que deseja marcar este bombeiro como INATIVO?')) {
          form.submit();
        }
      });
    }
  });

  // =====================================
  // Campos visíveis quando tipo = Fixo
  // =====================================
  const tipoSelect = document.getElementById('tipo');
  const fixoFields = document.getElementById('fixo-fields');
  if (tipoSelect && fixoFields) {
    const toggleFixoFields = () => {
      const isFixo = tipoSelect.value === 'Fixo';
      fixoFields.classList.toggle('visible', isFixo);
      fixoFields.querySelectorAll('input[type="date"], select').forEach(el => { el.required = isFixo; });
    };
    tipoSelect.addEventListener('change', toggleFixoFields);
    toggleFixoFields();
  }

  // ===============================
  // Modal de detalhes
  // ===============================
  const modal = document.getElementById("detailsModal");
  const modalTitle = document.getElementById("modalDate");
  const modalOcupantesList = document.getElementById("modalOcupantesList");
  const modalSelecaoDiv = document.getElementById("modalSelecao");
  const modalSugestao = document.getElementById("modalSugestao");
  const modalSelectBombeiro = document.getElementById("modalSelectBombeiro");

  const modalBtnD = document.getElementById("modalBtnD");
  const modalBtnN = document.getElementById("modalBtnN");
  const modalBtnI = document.getElementById("modalBtnI");
  const modalBtnISUB = document.getElementById("modalBtnISUB"); // NOVO

  const closeModalButton = document.querySelector(".modal .close-button");

  let currentModalDate = null;
  let currentVagas = { D: 0, N: 0, I_SUB: 0 };

  // Não abrir automaticamente — apenas por clique do usuário
  document.querySelectorAll('.btn-detalhes').forEach(button => {
    button.addEventListener('click', () => openModal(button));
  });

  if (closeModalButton) closeModalButton.addEventListener('click', closeModal);
  window.addEventListener('click', (event) => { if (event.target == modal) closeModal(); });

  if (modalSelectBombeiro) modalSelectBombeiro.addEventListener('change', handleBombeiroSelectionChange);

  async function openModal(button) {
    currentModalDate = button?.dataset?.date;
    if (!currentModalDate) { console.error("Botão sem data-date"); return; }

    modalTitle.textContent = 'Carregando...';
    modalOcupantesList.innerHTML = '<li>Carregando...</li>';
    modalSelecaoDiv.style.display = 'none';
    modalSelectBombeiro.innerHTML = '<option value="">-- Carregando --</option>';
    modalSelectBombeiro.disabled = true;
    modalSugestao.textContent = 'Sugestão: Carregando...';

    [modalBtnD, modalBtnN, modalBtnI, modalBtnISUB].forEach(b => { if (b) b.disabled = true; });
    currentVagas = { D: 0, N: 0, I_SUB: 0 };

    modal.style.display = "block";

    try {
      const data = await fetchJSON(`api/api_get_details.php?date=${currentModalDate}`);

      const [year, month, day] = currentModalDate.split('-');
      modalTitle.textContent = `${day}/${month}/${year}`;
      currentVagas = data.vagas || { D: 0, N: 0, I_SUB: 0 };

      // lista de ocupantes
      modalOcupantesList.innerHTML = '';
      if (data.fixo_calculado) {
        const fixo = data.fixo_calculado;
        const temExcecao = !!fixo.tem_excecao;
        let acaoBotaoHtml = temExcecao
          ? `<button class="btn-restore-fixo" data-bombeiro-id="${fixo.id}" data-date="${currentModalDate}" title="Restaurar ciclo">✅</button>`
          : `<button class="btn-add-excecao-fixo" data-bombeiro-id="${fixo.id}" data-date="${currentModalDate}" title="Remover do Ciclo (Exceção)">❌</button>`;
        modalOcupantesList.innerHTML += `
          <li class="${temExcecao ? 'tem-excecao' : ''}">
            <span class="bombeiro-info">${escapeHtml(fixo.nome_completo)} <span class="bombeiro-tipo">(Fixo - Ciclo)</span></span>
            <span class="turno-icon-wrapper">${getTurnoIconJS(null, true, temExcecao)}${acaoBotaoHtml}</span>
          </li>`;
      }
      if (data.extras && data.extras.length > 0) {
        data.extras.forEach(extra => {
          modalOcupantesList.innerHTML += `
            <li>
              <span class="bombeiro-info">${escapeHtml(extra.nome_completo)} <span class="bombeiro-tipo">(${escapeHtml(extra.tipo)})</span></span>
              <span class="turno-icon-wrapper">${getTurnoIconJS(extra.turno)}<button class="btn-remover-plantao" data-plantao-id="${extra.plantao_id}" title="Remover Extra">X</button></span>
            </li>`;
        });
      }
      if (modalOcupantesList.innerHTML === '') {
        modalOcupantesList.innerHTML = '<li>Nenhum bombeiro alocado.</li>';
      }

      // seleção
      modalSelecaoDiv.style.display = 'block';
      modalSelectBombeiro.disabled = false;
      modalSelectBombeiro.innerHTML = '<option value="">-- Selecione --</option>';
      if (data.bombeiros_ativos && data.bombeiros_ativos.length > 0) {
        data.bombeiros_ativos.forEach(b => {
          const opt = document.createElement('option');
          opt.value = b.id;
          opt.textContent = `${escapeHtml(b.nome_completo)} (${escapeHtml(b.tipo)})`;
          modalSelectBombeiro.appendChild(opt);
        });
      } else {
        modalSelectBombeiro.innerHTML = '<option value="">-- Nenhum ativo --</option>';
        modalSelectBombeiro.disabled = true;
      }

      if (data.proximo_sugerido && data.proximo_sugerido.id) {
        modalSugestao.innerHTML = `Sugestão: <strong>${escapeHtml(data.proximo_sugerido.nome)}</strong>`;
        const sugOpt = modalSelectBombeiro.querySelector(`option[value="${data.proximo_sugerido.id}"]`);
        if (sugOpt) sugOpt.selected = true;
      } else {
        modalSugestao.textContent = 'Sugestão: (Nenhum)';
      }

      handleBombeiroSelectionChange();
    } catch (error) {
      console.error('Erro ao buscar detalhes do dia:', error);
      modalTitle.textContent = `Erro de Comunicação`;
      modalOcupantesList.innerHTML = `<li>${escapeHtml(error.message || 'Não foi possível carregar os detalhes.')}</li>`;
    }
  }

  function closeModal() {
    if (modal) { modal.style.display = "none"; currentModalDate = null; }
  }

  function updateTurnoButton(button, turno) {
    if (!button) return;
    const vagasSpan = button.querySelector('.vagas');
    let isPossible = false;
    const bombeiroSelecionado = modalSelectBombeiro && modalSelectBombeiro.value !== '';

    if (turno === 'D') {
      isPossible = (currentVagas.D || 0) > 0;
      if (vagasSpan) vagasSpan.textContent = `(${currentVagas.D || 0} Vaga${(currentVagas.D || 0) !== 1 ? 's' : ''})`;
    } else if (turno === 'N') {
      isPossible = (currentVagas.N || 0) > 0;
      if (vagasSpan) vagasSpan.textContent = `(${currentVagas.N || 0} Vaga${(currentVagas.N || 0) !== 1 ? 's' : ''})`;
    } else if (turno === 'I') {
      isPossible = (currentVagas.D || 0) > 0 && (currentVagas.N || 0) > 0;
      if (vagasSpan) vagasSpan.textContent = isPossible ? "(OK)" : "(X)";
    } else if (turno === 'I_SUB') {
      isPossible = (currentVagas.I_SUB || 0) > 0;
      if (vagasSpan) vagasSpan.textContent = `(${currentVagas.I_SUB || 0})`;
    }

    button.disabled = !(isPossible && bombeiroSelecionado);
  }

  function handleBombeiroSelectionChange() {
    updateTurnoButton(modalBtnD, 'D');
    updateTurnoButton(modalBtnN, 'N');
    updateTurnoButton(modalBtnI, 'I');
    updateTurnoButton(modalBtnISUB, 'I_SUB');
  }

  // ===============================
  // Ações do modal
  // ===============================
  const registrarPlantao = async (turno) => {
    const bombeiroId = modalSelectBombeiro.value;
    const date = currentModalDate;
    if (!bombeiroId || !date) return;

    [modalBtnD, modalBtnN, modalBtnI, modalBtnISUB].forEach(b => { if (b) b.disabled = true; });

    const result = await postData('api/api_registrar_plantao.php', { bombeiro_id: bombeiroId, data: date, turno: turno });
    if (result.success) {
      showToast('Plantão registrado com sucesso!', 'success');
      handleSuccessfulAction(date);
    } else {
      showToast(result.message || 'Falha ao registrar.', 'error');
      handleBombeiroSelectionChange();
    }
  };

  if (modalBtnD) modalBtnD.addEventListener('click', () => registrarPlantao('D'));
  if (modalBtnN) modalBtnN.addEventListener('click', () => registrarPlantao('N'));
  if (modalBtnI) modalBtnI.addEventListener('click', () => registrarPlantao('I'));
  if (modalBtnISUB) modalBtnISUB.addEventListener('click', () => registrarPlantao('I_SUB')); // NOVO

  document.addEventListener('click', async (event) => {
    const target = event.target;

    if (target.closest('#modalOcupantesList')) {
      const date = currentModalDate; if (!date) return;
      let result;

      if (target.classList.contains('btn-remover-plantao')) {
        if (await showConfirm('Confirma remoção deste plantão?')) {
          result = await postData('api/api_remover_plantao.php', { plantao_id: target.dataset.plantaoId });
          if (result.success) showToast('Plantão removido.', 'success');
        } else { return; }
      } else if (target.classList.contains('btn-add-excecao-fixo')) {
        if (await showConfirm('Remover bombeiro do ciclo fixo para esta data?')) {
          result = await postData('api/api_registrar_excecao_fixo.php', { bombeiro_id: target.dataset.bombeiroId, data: date });
          if (result.success) showToast('Exceção adicionada.', 'success');
        } else { return; }
      } else if (target.classList.contains('btn-restore-fixo')) {
        if (await showConfirm('Restaurar bombeiro ao ciclo fixo para esta data?')) {
          result = await postData('api/api_remover_excecao_fixo.php', { bombeiro_id: target.dataset.bombeiroId, data: date });
          if (result.success) showToast('Ciclo restaurado.', 'success');
        } else { return; }
      }

      if (result && result.success) { handleSuccessfulAction(date); }
      else if (result) { showToast(result.message || 'Ação falhou.', 'error'); }
    }

    if (target.id === 'btnAvancarOrdem') {
      const btnAvancarOrdem = target;
      const displayProximoSugerido = document.getElementById('displayProximoSugerido');
      const originalButtonText = btnAvancarOrdem.textContent;
      btnAvancarOrdem.disabled = true;
      btnAvancarOrdem.textContent = 'Avançando...';
      try {
        const data = await fetchJSON('api/api_avancar_ordem.php', { method: 'POST' });
        if (displayProximoSugerido) { displayProximoSugerido.textContent = data.novo_nome || 'Ordem atualizada'; }
        showToast(data.message || 'Ordem avançada com sucesso.', 'success');
      } catch (error) {
        console.error("Erro ao avançar a ordem:", error);
        showToast('Erro na comunicação: ' + error.message, 'error');
      } finally {
        btnAvancarOrdem.disabled = false;
        btnAvancarOrdem.textContent = originalButtonText;
      }
    }
  });

  // --- INÍCIO: alterar início da ordem pelo <select> ---
  (function(){
    const sel = document.getElementById('selectInicioOrdem');
    const displayProximo = document.getElementById('displayProximoSugerido');
  
    if (!sel) return;
  
    const postData = async (url, data) => {
      try {
        const resp = await fetch(url, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams(data)
        });
        return await resp.json();
      } catch (e) {
        return { success:false, message: e.message || 'Erro de comunicação.' };
      }
    };
  
    sel.addEventListener('change', async () => {
      const id = sel.value;
      if (!id) return;
      // salva o novo início e retorna o "próximo sugerido" recalculado
      const r = await postData('api/api_set_inicio_ordem.php', { inicio_id: id });
      if (r.success) {
        if (displayProximo) displayProximo.textContent = r.proximo_nome || '(Nenhum)';
        if (typeof showToast === 'function') showToast('Início da ordem atualizado.', 'success');
      } else {
        if (typeof showToast === 'function') showToast(r.message || 'Falha ao atualizar início.', 'error');
      }
    });
  })();

  async function handleSuccessfulAction(date) {
    if (!date) return;
    closeModal();
    try {
      const data = await fetchJSON(`api/api_get_details.php?date=${date}`);
      updateCalendarCell(date, data);
    } catch (error) {
      console.error('Falha ao atualizar a célula:', error);
    }
  }

  // ===============================
  // Utilitários de UI
  // ===============================
  function getTurnoIconJS(turno, isFixoCycle = false, hasExcecao = false) {
    if (isFixoCycle) {
      return hasExcecao
        ? '<span class="turno-icon fixo-excecao" title="Fixo Removido (Exceção)">🚫</span>'
        : '<span class="turno-icon fixo-ciclo" title="Fixo - Ciclo 24h">⏰</span>';
    }
    switch (turno) {
      case 'D': return '<span class="turno-icon turno-D" title="Diurno">☀️</span>';
      case 'N': return '<span class="turno-icon turno-N" title="Noturno">🌑</span>';
      case 'I': return '<span class="turno-icon turno-I" title="Integral 24h">📅</span>';
      case 'I_SUB': return '<span class="turno-icon turno-I" title="Integral (Substituto do Fixo)">📅</span>';
      default: return '';
    }
  }

  function updateCalendarCell(date, cellData) {
    const button = document.querySelector(`.btn-detalhes[data-date="${date}"]`);
    if (!button) return;
    const cell = button.closest('td');
    if (!cell) return;

    const vagas = cellData.vagas || { D:0, N:0, I_SUB:0 };
    const statusDot = cell.querySelector('.status-dot');
    if (statusDot) statusDot.className = `status-dot ${ (vagas.D > 0 || vagas.N > 0) ? 'green' : 'red' }`;

    const vagaD = cell.querySelector('.availability-D');
    if (vagaD) {
      vagaD.textContent = `☀️ ${vagas.D}`;
      vagaD.className = `availability-slot availability-D ${vagas.D > 0 ? 'disponivel' : 'lotado'}`;
    }
    const vagaN = cell.querySelector('.availability-N');
    if (vagaN) {
      vagaN.textContent = `★ ${vagas.N}`;
      vagaN.className = `availability-slot availability-N ${vagas.N > 0 ? 'disponivel' : 'lotado'}`;
    }

    // badge SUB (I_SUB)
    let vagaISUB = cell.querySelector('.availability-ISUB');
    if (vagas.I_SUB > 0) {
      if (!vagaISUB) {
        const container = cell.querySelector('.cell-availability-info');
        if (container) {
          vagaISUB = document.createElement('span');
          vagaISUB.className = 'availability-slot availability-ISUB disponivel';
          vagaISUB.title = 'Vaga Integral (Substituto do Fixo)';
          vagaISUB.innerHTML = '<span class="turno-icon turno-I">📅</span> SUB';
          container.appendChild(vagaISUB);
        }
      } else {
        vagaISUB.className = 'availability-slot availability-ISUB disponivel';
      }
    } else if (vagaISUB) {
      vagaISUB.remove();
    }

    // badge I (integral BC normal – aparece só quando D e N livres)
    let vagaI = cell.querySelector('.availability-I');
    if (vagas.D > 0 && vagas.N > 0) {
      if (!vagaI) {
        const containerVagas = cell.querySelector('.cell-availability-info');
        if (containerVagas) {
          vagaI = document.createElement('span');
          vagaI.className = 'availability-slot availability-I disponivel';
          vagaI.title = 'Vaga Integral BC (24h) Disponível';
          vagaI.innerHTML = '📅';
          containerVagas.appendChild(vagaI);
        }
      }
    } else {
      if (vagaI) vagaI.remove();
    }

    // Lista de plantoes
    const plantoesContainer = cell.querySelector('.plantoes-do-dia');
    if (plantoesContainer) {
      let newHtml = '';
      if (cellData.fixo_calculado && !cellData.fixo_calculado.tem_excecao) {
        newHtml += `<span class="plantao-item fixo">${escapeHtml(abreviarNomeJS(cellData.fixo_calculado.nome_completo, 12))}${getTurnoIconJS(null, true)}</span>`;
      }
      if (cellData.extras) {
        cellData.extras.forEach(plantao => {
          newHtml += `<span class="plantao-item bc">${escapeHtml(abreviarNomeJS(plantao.nome_completo, 10))}${getTurnoIconJS(plantao.turno)}</span>`;
        });
      }
      plantoesContainer.innerHTML = newHtml;
    }
  }

  function abreviarNomeJS(nomeCompleto, maxLength = 12) {
    if (!nomeCompleto) return '';
    if (nomeCompleto.length <= maxLength) return nomeCompleto;
    const partes = nomeCompleto.trim().split(' ');
    if (partes.length > 1) {
      const primeiroNome = partes[0];
      const ultimaInicial = partes[partes.length - 1].charAt(0);
      const abreviado = `${primeiroNome} ${ultimaInicial}.`;
      return abreviado.length <= maxLength ? abreviado : primeiroNome.substring(0, maxLength - 2) + '..';
    }
    return nomeCompleto.substring(0, maxLength - 1) + '…';
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, (m) => {
      switch (m) {
        case '&': return '&amp;';
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '"': return '&quot;';
        case "'": return '&#39;';
        default: return m;
      }
    });
  }

  // ===============================
  // Tema escuro
  // ===============================
  const themeToggleButton = document.getElementById('theme-toggle');
  const body = document.body;

  const applyTheme = () => {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
      body.classList.add('dark-mode');
    } else {
      body.classList.remove('dark-mode');
    }
  };

  if (themeToggleButton) {
    themeToggleButton.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
      localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
    });
  }
  applyTheme();
});