(() => {
  const root = document.querySelector('[data-availability-calendar]');
  if (!root) return;
  const today = root.dataset.today;
  const days = JSON.parse(root.dataset.days);
  const month = new Date(today + 'T12:00:00');
  month.setDate(1);
  let selected = '';
  let blocked = false;
  const grid = root.querySelector('[data-calendar-days]');
  const hours = root.querySelector('[data-day-hours]');
  const message = root.querySelector('[data-day-message]');
  const intervalSelect = root.querySelector('[data-slot-interval]');
  const valuesFor = (key) => days[key]?.horarios || (Array.isArray(days[key]) ? days[key] : []);
  const keyFor = (date) => date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
  function renderMonth() {
    root.querySelector('[data-month-title]').textContent = month.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    grid.replaceChildren();
    for (let i = 0; i < month.getDay(); i++) grid.append(document.createElement('span'));
    const count = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
    for (let day = 1; day <= count; day++) {
      const key = keyFor(new Date(month.getFullYear(), month.getMonth(), day));
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = String(day);
      button.className = 'availability-day';
      button.dataset.date = key;
      const values = days[key] ? valuesFor(key) : null;
      const state = key === selected ? (blocked ? 'blocked' : 'available') : (values ? (values.length ? 'available' : 'blocked') : '');
      if (state) button.classList.add(state);
      if (key === selected) button.classList.add('selected');
      button.disabled = key < today;
      button.setAttribute('aria-label', key + ', ' + (state === 'blocked' ? 'bloqueado' : state === 'available' ? 'disponível' : 'regra semanal'));
      button.addEventListener('click', () => selectDay(key));
      button.addEventListener('dblclick', blockDay);
      grid.append(button);
    }
  }
  function selectDay(key) {
    if (selected === key) {
      blocked = false;
      paintSelection();
      return;
    }
    selected = key;
    blocked = false;
    intervalSelect.disabled = false;
    intervalSelect.value = String(days[key]?.intervalo || 30);
    root.querySelector('[data-selected-date]').value = key;
    root.querySelector('[data-day-title]').textContent = 'Horários de ' + key.split('-').reverse().join('/');
    root.querySelector('[data-save-day]').disabled = false;
    root.querySelector('[data-block-day]').disabled = false;
    message.textContent = 'Escolha os horários e clique em Salvar dia.';
    renderHours(valuesFor(key));
    paintSelection();
  }
  function renderHours(checkedValues) {
    hours.replaceChildren();
    const interval = Number(intervalSelect.value);
    for (let minute = 0; minute < 1440; minute += interval) {
      const value = String(Math.floor(minute / 60)).padStart(2, '0') + ':' + String(minute % 60).padStart(2, '0');
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.name = 'horarios[]';
      input.value = value;
      input.checked = checkedValues.includes(value);
      input.addEventListener('change', () => {
        blocked = false;
        paintSelection();
        message.textContent = 'Alterações pendentes. Clique em Salvar dia.';
      });
      label.append(input, ' ' + value);
      hours.append(label);
    }
  }
  function paintSelection() {
    grid.querySelectorAll('[data-date]').forEach(button => {
      const key = button.dataset.date;
      const values = days[key] ? valuesFor(key) : null;
      button.classList.toggle('selected', key === selected);
      button.classList.toggle('available', key === selected ? !blocked : Boolean(values && values.length));
      button.classList.toggle('blocked', key === selected ? blocked : Boolean(values && !values.length));
      button.setAttribute('aria-label', key + ', ' + (button.classList.contains('blocked') ? 'bloqueado' : button.classList.contains('available') ? 'disponível' : 'regra semanal'));
    });
  }
  function blockDay() {
    if (!selected) return;
    blocked = true;
    hours.querySelectorAll('input').forEach(input => { input.checked = false; });
    message.textContent = 'Dia bloqueado. Clique em Salvar dia para publicar.';
    paintSelection();
  }
  root.querySelector('[data-block-day]').addEventListener('click', blockDay);
  intervalSelect.addEventListener('change', () => {
    // Changing interval clears selection to avoid unintentionally expanding availability.
    renderHours([]);
    blocked = false;
    paintSelection();
    message.textContent = 'Intervalo alterado. Selecione os horários e salve o dia.';
  });
  root.querySelector('[data-month-prev]').addEventListener('click', () => { month.setMonth(month.getMonth() - 1); renderMonth(); });
  root.querySelector('[data-month-next]').addEventListener('click', () => { month.setMonth(month.getMonth() + 1); renderMonth(); });
  renderMonth();
})();
