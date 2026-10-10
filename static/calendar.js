'use strict';

window.Calendar = Calendar;

function Calendar(containerId) {
  var container = document.getElementById(containerId);
  if (!container) return null;

  var today = new Date();
  today.setHours(0, 0, 0, 0);
  var currentYear = today.getFullYear();
  var currentMonth = today.getMonth();
  var selectedDate = null;
  var availableDates = {};
  var dateStates = {};
  var availabilityLoaded = false;
  var weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  var months = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
  ];

  function pad(number) { return number < 10 ? '0' + number : String(number); }
  function formatDate(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
  }
  function sameDay(first, second) {
    return first && second && formatDate(first) === formatDate(second);
  }
  function monthValue() { return currentYear + '-' + pad(currentMonth + 1); }
  function notifyMonth() {
    container.dispatchEvent(new CustomEvent('calendarMonthChanged', {
      bubbles: true,
      detail: { month: monthValue() }
    }));
  }
  function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    availabilityLoaded = false;
    render();
    notifyMonth();
  }
  function render() {
    container.replaceChildren();
    var nav = document.createElement('div');
    nav.className = 'cal-nav';
    var previous = document.createElement('button');
    previous.type = 'button';
    previous.textContent = '‹';
    previous.setAttribute('aria-label', 'Mês anterior');
    previous.addEventListener('click', function () { changeMonth(-1); });
    var title = document.createElement('span');
    title.className = 'cal-title';
    title.textContent = months[currentMonth] + ' ' + currentYear;
    var next = document.createElement('button');
    next.type = 'button';
    next.textContent = '›';
    next.setAttribute('aria-label', 'Próximo mês');
    next.addEventListener('click', function () { changeMonth(1); });
    nav.append(previous, title, next);
    container.appendChild(nav);

    var grid = document.createElement('div');
    grid.className = 'cal-grid';
    weekdays.forEach(function (weekday) {
      var heading = document.createElement('div');
      heading.className = 'cal-head';
      heading.textContent = weekday;
      grid.appendChild(heading);
    });
    var firstDay = new Date(currentYear, currentMonth, 1).getDay();
    var daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    for (var emptyIndex = 0; emptyIndex < firstDay; emptyIndex++) {
      var empty = document.createElement('div');
      empty.className = 'cal-day cal-other';
      grid.appendChild(empty);
    }
    for (var day = 1; day <= daysInMonth; day++) {
      var date = new Date(currentYear, currentMonth, day);
      var value = formatDate(date);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'cal-day';
      button.textContent = String(day);
      var state = dateStates[value] || (availableDates[value] === true ? 'disponivel' : 'indisponivel');
      var enabled = date >= today && availabilityLoaded && state === 'disponivel';
      if (enabled) {
        button.classList.add('cal-available');
        button.dataset.date = value;
        button.addEventListener('click', selectDay);
      } else {
        button.disabled = true;
        button.classList.add('cal-disabled');
        if (date >= today && availabilityLoaded && state === 'agendado') {
          button.classList.add('cal-booked');
        }
      }
      if (sameDay(date, today)) button.classList.add('cal-today');
      if (sameDay(date, selectedDate)) button.classList.add('cal-selected');
      grid.appendChild(button);
    }
    container.appendChild(grid);
  }
  function selectDay(event) {
    var value = event.currentTarget.dataset.date;
    if (!value) return;
    selectedDate = new Date(value + 'T00:00:00');
    render();
    container.dispatchEvent(new CustomEvent('calendarDateSelected', {
      bubbles: true,
      detail: { date: value }
    }));
  }

  this.render = render;
  this.getMonth = monthValue;
  this.getSelectedDate = function () { return selectedDate ? formatDate(selectedDate) : null; };
  this.setAvailableDates = function (dates) {
    availableDates = {};
    dates.forEach(function (date) { availableDates[date] = true; });
    dateStates = {};
    availabilityLoaded = true;
    render();
  };
  this.setDateStates = function (states) {
    dateStates = states || {};
    availableDates = {};
    Object.keys(dateStates).forEach(function (date) {
      availableDates[date] = dateStates[date] === 'disponivel';
    });
    availabilityLoaded = true;
    render();
  };
  render();
}
