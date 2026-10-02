'use strict';

(function () {
  var servicoSelect = document.getElementById('servico_id');
  var profSelect = document.getElementById('profissional_id');
  var profField = document.getElementById('profField');
  var dataInput = document.getElementById('data');
  var horaInput = document.getElementById('hora_inicio');
  var slotsContainer = document.getElementById('slots-container');
  var slotsLoading = document.getElementById('slots-loading');
  var slotsVazio = document.getElementById('slots-vazio');
  var resumoDiv = document.getElementById('resumo');
  var calContainer = document.getElementById('calendar-container');

  var steps = ['step1', 'step2', 'step3', 'step4'];
  var state = { servico: null, profissional: null, data: null, hora: null };

  function showStep(n) {
    for (var i = 0; i < steps.length; i++) {
      var el = document.getElementById(steps[i]);
      if (el) el.classList.toggle('active', i <= n);
    }
  }

  // Step 1: Serviço → carregar profissionais
  servicoSelect.addEventListener('change', function () {
    var val = servicoSelect.value;
    if (!val) { profField.style.display = 'none'; showStep(0); return; }
    state.servico = val;
    state.profissional = null;
    state.data = null;
    state.hora = null;
    profField.style.display = 'block';
    profSelect.innerHTML = '<option value="">Carregando...</option>';
    showStep(0);

    window.fetch('/api/profissionais.php?servico=' + encodeURIComponent(val))
      .then(function (r) { return r.json(); })
      .then(function (profs) {
        profSelect.innerHTML = '<option value="">Selecione</option>';
        for (var i = 0; i < profs.length; i++) {
          var opt = document.createElement('option');
          opt.value = profs[i].id;
          opt.textContent = profs[i].nome;
          profSelect.appendChild(opt);
        }
        if (profs.length === 1) {
          profSelect.value = profs[0].id;
          profSelect.dispatchEvent(new window.Event('change'));
        }
      });
  });

  // Step 1b: Profissional → mostrar calendário
  profSelect.addEventListener('change', function () {
    var val = profSelect.value;
    if (!val) { showStep(0); return; }
    state.profissional = val;
    state.data = null;
    state.hora = null;
    showStep(1);
    if (!calContainer.hasChildNodes()) {
      new window.Calendar('calendar-container');
    }
  });

  // Step 2: Data selecionada → carregar slots
  document.addEventListener('calendarDateSelected', function (e) {
    var dateStr = e.detail.date;
    state.data = dateStr;
    state.hora = null;
    dataInput.value = dateStr;
    horaInput.value = '';
    slotsContainer.innerHTML = '';
    slotsVazio.style.display = 'none';
    slotsLoading.style.display = 'block';
    showStep(2);

    var url = '/api/slots.php?servico=' + encodeURIComponent(state.servico)
      + '&profissional=' + encodeURIComponent(state.profissional)
      + '&data=' + encodeURIComponent(dateStr);

    window.fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        slotsLoading.style.display = 'none';
        slotsContainer.innerHTML = '';

        if (!data.horarios || data.horarios.length === 0) {
          slotsVazio.style.display = 'block';
          return;
        }

        for (var i = 0; i < data.horarios.length; i++) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'slot-btn';
          btn.textContent = data.horarios[i];
          btn.dataset.hora = data.horarios[i];
          btn.addEventListener('click', onSlotClick);
          slotsContainer.appendChild(btn);
        }
      })
      .catch(function () {
        slotsLoading.style.display = 'none';
        slotsVazio.style.display = 'block';
        slotsVazio.textContent = 'Erro ao carregar horários.';
      });
  });

  // Step 3: Horário selecionado → exibir formulário
  function onSlotClick(e) {
    var hora = e.currentTarget.dataset.hora;
    state.hora = hora;
    horaInput.value = hora;

    var btns = slotsContainer.querySelectorAll('.slot-btn');
    for (var i = 0; i < btns.length; i++) {
      btns[i].classList.toggle('selected', btns[i].dataset.hora === hora);
    }

    showStep(3);
    atualizarResumo();
  }

  function atualizarResumo() {
    var sOpt = servicoSelect.options[servicoSelect.selectedIndex];
    var pOpt = profSelect.options[profSelect.selectedIndex];
    var preco = sOpt ? sOpt.dataset.preco : '';

    resumoDiv.innerHTML = '<p><strong>Serviço:</strong> '
      + esc(sOpt ? sOpt.textContent.trim() : '') + '</p>'
      + '<p><strong>Profissional:</strong> '
      + esc(pOpt ? pOpt.textContent.trim() : '') + '</p>'
      + '<p><strong>Data:</strong> ' + formatarData(state.data) + '</p>'
      + '<p><strong>Horário:</strong> ' + esc(state.hora || '') + '</p>'
      + (preco ? '<p><strong>Valor:</strong> R$ ' + esc(preco) + '</p>' : '');
  }

  function formatarData(dateStr) {
    if (!dateStr) return '';
    var p = dateStr.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
  }
})();
