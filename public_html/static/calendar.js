'use strict';

/**
 * Calendário vanilla JS para agendamento.
 * Dispara evento 'calendarDateSelected' com detail.date (YYYY-MM-DD).
 */
window.Calendar = Calendar;

function Calendar(containerId) {
  var container = document.getElementById(containerId);
  if (!container) return;

  var today = new Date();
  today.setHours(0, 0, 0, 0);

  var currentYear = today.getFullYear();
  var currentMonth = today.getMonth();
  var selectedDate = null;

  var DIAS_SEMANA = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  var MESES = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
  ];

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function formatDate(d) {
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }

  function isSameDay(a, b) {
    return a && b &&
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate();
  }

  function render() {
    container.innerHTML = '';

    var nav = document.createElement('div');
    nav.className = 'cal-nav';

    var btnPrev = document.createElement('button');
    btnPrev.type = 'button';
    btnPrev.textContent = '‹';
    btnPrev.setAttribute('aria-label', 'Mês anterior');
    btnPrev.addEventListener('click', function () {
      currentMonth--;
      if (currentMonth < 0) { currentMonth = 11; currentYear--; }
      render();
    });

    var btnNext = document.createElement('button');
    btnNext.type = 'button';
    btnNext.textContent = '›';
    btnNext.setAttribute('aria-label', 'Próximo mês');
    btnNext.addEventListener('click', function () {
      currentMonth++;
      if (currentMonth > 11) { currentMonth = 0; currentYear++; }
      render();
    });

    var title = document.createElement('span');
    title.className = 'cal-title';
    title.textContent = MESES[currentMonth] + ' ' + currentYear;

    nav.appendChild(btnPrev);
    nav.appendChild(title);
    nav.appendChild(btnNext);
    container.appendChild(nav);

    var grid = document.createElement('div');
    grid.className = 'cal-grid';

    // Cabeçalho dos dias da semana
    for (var h = 0; h < 7; h++) {
      var head = document.createElement('div');
      head.className = 'cal-head';
      head.textContent = DIAS_SEMANA[h];
      grid.appendChild(head);
    }

    // Primeiro dia do mês e quantos dias
    var firstDay = new Date(currentYear, currentMonth, 1).getDay();
    var daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    // Células vazias antes do primeiro dia
    for (var e = 0; e < firstDay; e++) {
      var empty = document.createElement('div');
      empty.className = 'cal-day cal-other';
      grid.appendChild(empty);
    }

    // Dias do mês
    for (var d = 1; d <= daysInMonth; d++) {
      var date = new Date(currentYear, currentMonth, d);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'cal-day';
      btn.textContent = d;

      if (date < today) {
        btn.classList.add('cal-disabled');
      } else {
        btn.dataset.date = formatDate(date);
        btn.addEventListener('click', onDayClick);
      }

      if (isSameDay(date, today)) {
        btn.classList.add('cal-today');
      }
      if (isSameDay(date, selectedDate)) {
        btn.classList.add('cal-selected');
      }

      grid.appendChild(btn);
    }

    container.appendChild(grid);
  }

  function onDayClick(e) {
    var dateStr = e.currentTarget.dataset.date;
    if (!dateStr) return;

    selectedDate = new Date(dateStr + 'T00:00:00');
    render();

    container.dispatchEvent(new CustomEvent('calendarDateSelected', {
      bubbles: true,
      detail: { date: dateStr }
    }));
  }

  // API pública
  this.render = render;
  this.getSelectedDate = function () {
    return selectedDate ? formatDate(selectedDate) : null;
  };

  render();
}
