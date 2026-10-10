'use strict';

(function () {
  var serviceSelect = document.getElementById('servico_id');
  var professionalInput = document.getElementById('profissional_id');
  var cart = document.getElementById('carrinho-servicos');
  var serviceInputs = document.getElementById('servicos-ids');
  var professionalCards = document.getElementById('professional-cards');
  var professionalField = document.getElementById('profField');
  var slots = document.getElementById('slots-container');
  var slotsLoading = document.getElementById('slots-loading');
  var slotsEmpty = document.getElementById('slots-vazio');
  var calendarStatus = document.getElementById('calendar-status');
  var summary = document.getElementById('resumo');
  var confirmButton = document.getElementById('btnConfirmar');
  var state = { services: [], professional: null, date: '', hour: '', calendar: null };

  function money(value) {
    return value === null ? 'Consultar valor' : new Intl.NumberFormat('pt-BR', {
      style: 'currency', currency: 'BRL'
    }).format(Number(value));
  }
  function duration(minutes) {
    var hours = Math.floor(minutes / 60);
    var rest = minutes % 60;
    return (hours ? hours + 'h' : '') + (rest ? (hours ? String(rest).padStart(2, '0') : rest) + 'min' : '00min');
  }
  function finishTime() {
    if (!state.hour) return '';
    var total = state.services.reduce(function (sum, service) { return sum + service.duration; }, 0);
    var parts = state.hour.split(':').map(Number);
    var minutes = parts[0] * 60 + parts[1] + total;
    return String(Math.floor(minutes / 60) % 24).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');
  }
  function apiQuery() {
    var params = new window.URLSearchParams();
    state.services.forEach(function (service) { params.append('servicos[]', service.id); });
    return params;
  }
  function request(url) {
    return window.fetch(url).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok) throw new Error(data.erro || 'Não foi possível carregar os dados.');
        return data;
      });
    });
  }
  function setActiveStep(id) {
    document.getElementById(id).classList.add('active');
  }
  function clearSchedule() {
    state.professional = null;
    state.date = '';
    state.hour = '';
    state.calendar = null;
    professionalInput.value = '';
    document.getElementById('data').value = '';
    document.getElementById('hora_inicio').value = '';
    professionalCards.replaceChildren();
    document.getElementById('calendar-container').replaceChildren();
    slots.replaceChildren();
    slotsLoading.hidden = true;
    slotsEmpty.hidden = true;
    calendarStatus.textContent = '';
    ['step2', 'step3', 'step4'].forEach(function (id) {
      document.getElementById(id).classList.remove('active');
    });
  }
  function updateConfirmButton() {
    confirmButton.disabled = !(
      state.services.length && state.professional && state.date && state.hour
      && document.getElementById('cliente_nome').value.trim().length >= 2
      && /^\d{10,15}$/.test(document.getElementById('cliente_telefone').value.replace(/\D/g, ''))
    );
  }
  function renderCart() {
    serviceInputs.replaceChildren();
    cart.replaceChildren();
    if (!state.services.length) {
      cart.textContent = 'Adicione um ou mais serviços.';
      return;
    }
    var heading = document.createElement('strong');
    heading.textContent = 'Serviços selecionados';
    cart.appendChild(heading);
    state.services.forEach(function (service) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'servico_ids[]'; input.value = service.id;
      serviceInputs.appendChild(input);
      var row = document.createElement('div'); row.className = 'cart-row';
      var description = document.createElement('span');
      description.textContent = service.name + ' — ' + duration(service.duration) + ' — ' + money(service.price);
      var remove = document.createElement('button');
      remove.type = 'button'; remove.className = 'cart-remove'; remove.textContent = 'Remover';
      remove.addEventListener('click', function () {
        state.services = state.services.filter(function (item) { return item.id !== service.id; });
        clearSchedule(); render(); loadProfessionals();
      });
      row.append(description, remove); cart.appendChild(row);
    });
  }
  function renderSummary() {
    summary.replaceChildren();
    var totalDuration = state.services.reduce(function (sum, service) { return sum + service.duration; }, 0);
    var totalPrice = state.services.reduce(function (sum, service) { return sum + (service.price || 0); }, 0);
    var title = document.createElement('strong'); title.textContent = 'Resumo do agendamento'; summary.appendChild(title);
    state.services.forEach(function (service) {
      var item = document.createElement('p'); item.textContent = service.name + ' — ' + money(service.price); summary.appendChild(item);
    });
    [['Duração', totalDuration ? duration(totalDuration) : '—'], ['Total', state.services.length ? money(totalPrice) : '—'],
      ['Profissional', state.professional ? state.professional.name : '—'], ['Data', state.date ? state.date.split('-').reverse().join('/') : '—'],
      ['Horário', state.hour ? state.hour + ' às ' + finishTime() : '—']].forEach(function (entry) {
      var line = document.createElement('p');
      var label = document.createElement('b'); label.textContent = entry[0] + ': ';
      line.append(label, document.createTextNode(entry[1])); summary.appendChild(line);
    });
    updateConfirmButton();
  }
  function render() { renderCart(); renderSummary(); }
  function loadProfessionals() {
    if (!state.services.length) { professionalField.hidden = true; return; }
    professionalField.hidden = false;
    professionalCards.textContent = 'Carregando profissionais…';
    var params = apiQuery();
    request('/api/profissionais.php?' + params.toString()).then(function (people) {
      professionalCards.replaceChildren();
      if (!people.length) {
        professionalCards.textContent = 'Nenhum profissional atende todos os serviços selecionados.';
        return;
      }
      people.forEach(function (person) {
        var card = document.createElement('button');
        card.type = 'button'; card.className = 'professional-card';
        card.setAttribute('role', 'radio'); card.setAttribute('aria-checked', 'false');
        if (person.foto_url) {
          var image = document.createElement('img'); image.src = person.foto_url;
          image.alt = ''; image.className = 'professional-photo'; card.appendChild(image);
        }
        var details = document.createElement('span'); details.className = 'professional-details';
        var name = document.createElement('strong'); name.textContent = person.nome; details.appendChild(name);
        if (person.especialidade) {
          var specialty = document.createElement('small'); specialty.textContent = person.especialidade; details.appendChild(specialty);
        }
        card.appendChild(details);
        card.addEventListener('click', function () { selectProfessional(person, card); });
        professionalCards.appendChild(card);
      });
    }).catch(function (error) { professionalCards.textContent = error.message; });
  }
  function selectProfessional(person, card) {
    state.professional = person;
    state.date = ''; state.hour = '';
    professionalInput.value = person.id;
    document.getElementById('data').value = '';
    document.getElementById('hora_inicio').value = '';
    professionalCards.querySelectorAll('.professional-card').forEach(function (item) {
      var selected = item === card; item.classList.toggle('selected', selected); item.setAttribute('aria-checked', String(selected));
    });
    slots.replaceChildren(); slotsEmpty.hidden = true;
    setActiveStep('step2');
    state.calendar = new window.Calendar('calendar-container');
    loadAvailability(state.calendar.getMonth());
    render();
  }
  function loadAvailability(month) {
    if (!state.professional || !state.calendar) return;
    calendarStatus.textContent = 'Consultando dias disponíveis…';
    var params = apiQuery(); params.set('profissional', state.professional.id); params.set('mes', month);
    request('/api/disponibilidade.php?' + params.toString()).then(function (data) {
      if (!state.calendar || state.calendar.getMonth() !== month) return;
      state.calendar.setAvailableDates(data.dias || []);
      calendarStatus.textContent = data.dias.length ? 'Dias em verde possuem horários disponíveis.' : 'Não há horários disponíveis neste mês.';
    }).catch(function (error) { calendarStatus.textContent = error.message; });
  }
  function loadSlots() {
    var params = apiQuery(); params.set('profissional', state.professional.id); params.set('data', state.date);
    slots.replaceChildren(); slotsLoading.hidden = false; slotsEmpty.hidden = true;
    request('/api/slots.php?' + params.toString()).then(function (data) {
      slotsLoading.hidden = true;
      slotsEmpty.hidden = !(data.horarios || []).length;
      (data.horarios || []).forEach(function (hour) {
        var button = document.createElement('button'); button.type = 'button'; button.className = 'slot-btn'; button.textContent = hour;
        button.addEventListener('click', function () {
          state.hour = hour; document.getElementById('hora_inicio').value = hour;
          slots.querySelectorAll('.slot-btn').forEach(function (item) { item.classList.toggle('selected', item === button); });
          setActiveStep('step4'); render();
        });
        slots.appendChild(button);
      });
    }).catch(function (error) { slotsLoading.hidden = true; slotsEmpty.hidden = false; slotsEmpty.textContent = error.message; });
  }
  serviceSelect.addEventListener('change', function () {
    var option = serviceSelect.options[serviceSelect.selectedIndex];
    if (!option.value || state.services.some(function (service) { return service.id === option.value; })) return;
    state.services.push({ id: option.value, name: option.textContent.replace(/\s+/g, ' ').trim(), duration: Number(option.dataset.duracao), price: option.dataset.preco === 'Consultar' ? null : Number(option.dataset.preco.replace('.', '').replace(',', '.')) });
    serviceSelect.value = ''; clearSchedule(); render(); loadProfessionals();
  });
  document.addEventListener('calendarMonthChanged', function (event) { loadAvailability(event.detail.month); });
  document.addEventListener('calendarDateSelected', function (event) {
    if (!state.professional) return;
    state.date = event.detail.date; state.hour = ''; document.getElementById('data').value = state.date;
    document.getElementById('hora_inicio').value = ''; setActiveStep('step3'); render(); loadSlots();
  });
  ['cliente_nome', 'cliente_telefone'].forEach(function (id) { document.getElementById(id).addEventListener('input', updateConfirmButton); });
  render();
})();
