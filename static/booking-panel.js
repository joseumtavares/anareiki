'use strict';

(function () {
  var panel = document.getElementById('booking-panel');
  if (!panel) return;

  var state = { services: [], professional: null, date: '', serviceTimes: {}, calendar: null, submitting: false };
  var elements = {
    form: document.getElementById('booking-content'),
    toggle: document.getElementById('booking-toggle'), close: document.getElementById('booking-close'),
    count: document.getElementById('booking-count'), summary: document.getElementById('booking-summary'),
    serviceIds: document.getElementById('booking-service-ids'), professionals: document.getElementById('booking-professionals'),
    professionalsHelp: document.getElementById('booking-professionals-help'), dateTime: document.getElementById('booking-date-time'),
    details: document.getElementById('booking-details'), dateStatus: document.getElementById('booking-date-status'),
    detailsStatus: document.getElementById('booking-details-status'), calendarStatus: document.getElementById('calendar-status'),
    slots: document.getElementById('slots-container'), slotsDate: document.getElementById('booking-slots-date'),
    slotsLoading: document.getElementById('slots-loading'), slotsEmpty: document.getElementById('slots-vazio'),
    professional: document.getElementById('profissional_id'), date: document.getElementById('data'),
    hour: document.getElementById('hora_inicio'), name: document.getElementById('cliente_nome'),
    phone: document.getElementById('cliente_telefone'), confirm: document.getElementById('btnConfirmar'),
    submitStatus: document.getElementById('booking-submit-status')
  };

  function formatMoney(value) {
    return value === null ? 'Consultar valor' : new Intl.NumberFormat('pt-BR', {
      style: 'currency', currency: 'BRL'
    }).format(value);
  }
  function formatDuration(minutes) {
    var hours = Math.floor(minutes / 60); var rest = minutes % 60;
    return (hours ? hours + 'h' : '') + (rest ? (hours ? String(rest).padStart(2, '0') : rest) + 'min' : '00min');
  }
  function totalDuration() { return state.services.reduce(function (sum, item) { return sum + item.duration; }, 0); }
  function totalPrice() { return state.services.reduce(function (sum, item) { return sum + (item.price || 0); }, 0); }
  function finish(startTime, duration) {
    if (!startTime) return '';
    var start = startTime.split(':').map(Number); var minutes = start[0] * 60 + start[1] + duration;
    return String(Math.floor(minutes / 60) % 24).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');
  }
  function params() {
    var values = new window.URLSearchParams();
    state.services.forEach(function (item) { values.append('servicos[]', item.id); });
    return values;
  }
  function request(url) {
    return window.fetch(url).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok) throw new Error(data.erro || 'Não foi possível carregar os dados.');
        return data;
      });
    });
  }
  function setOpen(open) {
    panel.classList.toggle('is-open', open);
    elements.toggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('booking-panel-open', open);
    if (open) elements.close.focus(); else elements.toggle.focus();
  }
  function clearSelection() {
    state.professional = null; state.date = ''; state.serviceTimes = {}; state.calendar = null;
    elements.professional.value = ''; elements.date.value = ''; elements.hour.value = '';
    elements.professionals.replaceChildren(); elements.slots.replaceChildren();
    document.getElementById('calendar-container').replaceChildren();
    elements.dateTime.open = false; elements.details.open = false;
    elements.dateTime.classList.add('is-disabled'); elements.details.classList.add('is-disabled');
    elements.dateStatus.textContent = 'Selecione um profissional';
    elements.detailsStatus.textContent = 'Selecione um horário';
    elements.slotsDate.textContent = 'Escolha uma data disponível.';
  }
  function updateConfirm() {
    elements.confirm.disabled = !(
      state.services.length && state.professional && state.date
      && state.services.every(function (service) { return Boolean(state.serviceTimes[service.id]); })
      && elements.name.value.trim().length >= 2 && /^\d{10,15}$/.test(elements.phone.value.replace(/\D/g, ''))
      && !state.submitting
    );
  }
  function renderSummary() {
    elements.summary.replaceChildren(); elements.serviceIds.replaceChildren();
    var title = document.createElement('strong'); title.textContent = state.services.length ? 'Sua seleção' : 'Nenhum serviço selecionado';
    elements.summary.appendChild(title);
    state.services.forEach(function (item) {
      var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'servico_ids[]'; hidden.value = item.id;
      elements.serviceIds.appendChild(hidden);
      if (state.serviceTimes[item.id]) {
        var time = document.createElement('input'); time.type = 'hidden';
        time.name = 'horarios_por_servico[' + item.id + ']';
        time.value = state.serviceTimes[item.id].inicio;
        elements.serviceIds.appendChild(time);
      }
      var row = document.createElement('div'); row.className = 'booking-summary-item';
      var label = document.createElement('span'); label.textContent = item.name + ' — ' + formatDuration(item.duration) + ' — ' + formatMoney(item.price);
      var remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Remover'; remove.className = 'booking-remove';
      remove.addEventListener('click', function () { removeService(item.id); });
      row.append(label, remove); elements.summary.appendChild(row);
    });
    var total = document.createElement('p'); total.className = 'booking-total';
    total.textContent = state.services.length ? 'Total: ' + formatMoney(totalPrice()) + ' · ' + formatDuration(totalDuration()) : 'Escolha seus serviços na página.';
    elements.summary.appendChild(total);
    if (state.professional || state.date || Object.keys(state.serviceTimes).length) {
      var details = document.createElement('p'); details.className = 'booking-help';
      details.textContent = 'Profissional: ' + (state.professional ? state.professional.nome : '—')
        + ' · Data: ' + (state.date ? state.date.split('-').reverse().join('/') : '—')
        + ' · Horário: ' + (state.hour ? state.hour + ' às ' + finish() : '—');
      elements.summary.appendChild(details);
    }
    state.services.forEach(function (service) {
      var selectedTime = state.serviceTimes[service.id];
      if (!selectedTime) return;
      var detail = document.createElement('p'); detail.className = 'booking-help';
      detail.textContent = service.name + ': ' + selectedTime.inicio + ' às ' + selectedTime.fim;
      elements.summary.appendChild(detail);
    });
    elements.count.textContent = String(state.services.length); updateConfirm();
  }
  function render() { renderSummary(); }
  function loadProfessionals() {
    if (!state.services.length) { elements.professionalsHelp.textContent = 'Adicione um serviço para ver profissionais.'; return; }
    elements.professionalsHelp.textContent = 'Carregando profissionais…';
    request('/api/profissionais.php?' + params().toString()).then(function (people) {
      elements.professionals.replaceChildren();
      if (!people.length) { elements.professionalsHelp.textContent = 'Nenhum profissional atende todos os serviços.'; return; }
      elements.professionalsHelp.textContent = 'Escolha quem irá atender você.';
      people.forEach(function (person) {
        var button = document.createElement('button'); button.type = 'button'; button.className = 'booking-professional';
        button.setAttribute('role', 'radio'); button.setAttribute('aria-checked', 'false');
        if (person.foto_url) { var image = document.createElement('img'); image.src = person.foto_url; image.alt = ''; button.appendChild(image); }
        var name = document.createElement('strong'); name.textContent = person.nome; button.appendChild(name);
        if (person.especialidade) { var specialty = document.createElement('small'); specialty.textContent = person.especialidade; button.appendChild(specialty); }
        button.addEventListener('click', function () { selectProfessional(person, button); }); elements.professionals.appendChild(button);
      });
    }).catch(function (error) { elements.professionalsHelp.textContent = error.message; });
  }
  function addService(service) {
    if (state.services.some(function (item) { return item.id === service.id; })) { setOpen(true); return; }
    state.services.push(service); clearSelection(); render(); loadProfessionals(); setOpen(true);
  }
  function removeService(id) {
    state.services = state.services.filter(function (item) { return item.id !== id; }); clearSelection(); render(); loadProfessionals();
  }
  function selectProfessional(person, button) {
    state.professional = person; state.date = ''; state.serviceTimes = {}; elements.professional.value = person.id;
    elements.date.value = ''; elements.hour.value = ''; elements.slots.replaceChildren(); elements.slotsEmpty.hidden = true;
    elements.professionals.querySelectorAll('.booking-professional').forEach(function (item) {
      var selected = item === button; item.classList.toggle('selected', selected); item.setAttribute('aria-checked', String(selected));
    });
    elements.dateTime.open = true; elements.dateStatus.textContent = 'Escolha a data';
    elements.dateTime.classList.remove('is-disabled');
    state.calendar = new window.Calendar('calendar-container'); loadAvailability(state.calendar.getMonth()); render();
  }
  function loadAvailability(month) {
    if (!state.professional || !state.calendar) return;
    elements.calendarStatus.textContent = 'Consultando dias disponíveis…'; var values = params();
    values.set('profissional', state.professional.id); values.set('mes', month);
    request('/api/disponibilidade.php?' + values.toString()).then(function (data) {
      if (!state.calendar || state.calendar.getMonth() !== month) return;
      state.calendar.setDateStates(data.estados || {});
      elements.calendarStatus.textContent = data.dias.length ? 'Dias em verde possuem horários disponíveis.' : 'Não há horários neste mês.';
    }).catch(function (error) { elements.calendarStatus.textContent = error.message; });
  }
  function loadSlots() {
    var values = params(); values.set('profissional', state.professional.id); values.set('data', state.date);
    elements.slots.replaceChildren(); elements.slotsLoading.hidden = false; elements.slotsEmpty.hidden = true;
    request('/api/slots.php?' + values.toString()).then(function (data) {
      var grades = data.grades_por_servico || [];
      var hasAvailableSlot = grades.some(function (grade) {
        return (grade.slots || []).some(function (slot) { return slot.estado === 'disponivel'; });
      });
      elements.slotsLoading.hidden = true; elements.slotsEmpty.hidden = hasAvailableSlot;
      grades.forEach(function (grade) { renderServiceSlots(grade); });
    }).catch(function (error) { elements.slotsLoading.hidden = true; elements.slotsEmpty.hidden = false; elements.slotsEmpty.textContent = error.message; });
  }

  function conflictsWithSelection(serviceId, slot) {
    return Object.keys(state.serviceTimes).some(function (id) {
      var selected = state.serviceTimes[id];
      return id !== serviceId && slot.inicio < selected.fim && slot.fim > selected.inicio;
    });
  }

  function renderServiceSlots(grade) {
    var section = document.createElement('section'); section.className = 'service-slots';
    var title = document.createElement('h4'); title.textContent = grade.nome + ' (' + formatDuration(grade.duracao_min) + ')';
    var grid = document.createElement('div'); grid.className = 'slots-grid';
    (grade.slots || []).forEach(function (slot) {
      var button = document.createElement('button'); button.type = 'button'; button.className = 'slot-btn';
      button.textContent = slot.inicio + ' - ' + slot.fim;
      var selected = state.serviceTimes[grade.id] && state.serviceTimes[grade.id].inicio === slot.inicio;
      var unavailable = slot.estado !== 'disponivel' || conflictsWithSelection(grade.id, slot);
      button.classList.add(selected ? 'is-selected' : (unavailable ? 'is-unavailable' : 'is-available'));
      button.disabled = unavailable && !selected;
      if (!unavailable || selected) {
        button.addEventListener('click', function () {
          state.serviceTimes[grade.id] = { inicio: slot.inicio, fim: slot.fim };
          elements.hour.value = slot.inicio;
          if (state.services.every(function (service) { return Boolean(state.serviceTimes[service.id]); })) {
            elements.details.open = true; elements.detailsStatus.textContent = 'Preencha seus dados';
            elements.details.classList.remove('is-disabled');
          }
          render(); loadSlots();
        });
      }
      grid.appendChild(button);
    });
    section.append(title, grid); elements.slots.appendChild(section);
  }

  function enviarAgendamento() {
    state.submitting = true; updateConfirm();
    elements.submitStatus.hidden = false;
    elements.submitStatus.textContent = 'Validando e registrando seu agendamento…';
    window.fetch(elements.form.action, {
      method: 'POST', body: new window.FormData(elements.form),
      headers: { Accept: 'application/json' }, credentials: 'same-origin'
    }).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.whatsapp_url) {
          throw new Error(data.erro || 'Não foi possível confirmar o agendamento.');
        }
        return data;
      });
    }).then(function (data) {
      window.location.assign(data.whatsapp_url);
    }).catch(function (error) {
      state.submitting = false; updateConfirm();
      elements.submitStatus.textContent = error.message;
    });
  }

  document.querySelectorAll('[data-booking-service]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      event.preventDefault();
      addService({ id: link.dataset.bookingService, name: link.dataset.serviceName, duration: Number(link.dataset.serviceDuration), price: link.dataset.servicePrice === '' ? null : Number(link.dataset.servicePrice) });
    });
  });
  elements.toggle.addEventListener('click', function () { setOpen(!panel.classList.contains('is-open')); });
  document.querySelectorAll('[data-booking-open]').forEach(function (link) {
    link.addEventListener('click', function (event) { event.preventDefault(); event.stopImmediatePropagation(); setOpen(true); });
  });
  elements.close.addEventListener('click', function () { setOpen(false); });
  elements.dateTime.addEventListener('toggle', function () {
    if (elements.dateTime.open && !state.professional) elements.dateTime.open = false;
  });
  elements.details.addEventListener('toggle', function () {
    if (elements.details.open && !state.services.every(function (service) { return Boolean(state.serviceTimes[service.id]); })) {
      elements.details.open = false;
    }
  });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false); });
  document.addEventListener('calendarMonthChanged', function (event) { loadAvailability(event.detail.month); });
  document.addEventListener('calendarDateSelected', function (event) {
    if (!state.professional) return;
    state.date = event.detail.date; state.serviceTimes = {}; elements.date.value = state.date; elements.hour.value = '';
    elements.slotsDate.textContent = state.date.split('-').reverse().join('/'); elements.dateStatus.textContent = 'Data selecionada'; render(); loadSlots();
  });
  [elements.name, elements.phone].forEach(function (input) { input.addEventListener('input', updateConfirm); });
  elements.form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (elements.confirm.disabled || !elements.form.reportValidity()) return;
    enviarAgendamento();
  });
  window.BookingPanel = { addService: addService, open: function () { setOpen(true); }, close: function () { setOpen(false); }, render: render };
  render();
})();
