let timer = document.querySelector('[test-elem="timer"]')

if (timer) {
  let backTimerUrl = timer.getAttribute('back-timer-url')

  function getServerTimestamp() {
    return fetch(backTimerUrl)
      .then(response => response.text())
      .then(timestamp => {
        timer.setAttribute('cur-time', timestamp)
        countdownTimer()
      })
  }

  function countdownTimer() {
    let curServerTime = timer.getAttribute('cur-time')
    let currentDate = new Date(curServerTime * 1000)
    let diff = endDate - currentDate;
    if (diff <= 0) {
      clearInterval(timerId);

      document.dispatchEvent(new Event('ticketTimeout'))
    }

    printCountdown(diff)

    timer.setAttribute('cur-time', parseInt(curServerTime) + 1)
  }

  function printCountdown(diff) {
    let minutes = diff > 0 ? Math.floor(diff / 1000 / 60) % 60 : 0;
    let seconds = diff > 0 ? Math.floor(diff / 1000) % 60 : 0;

    let formatMinutes = minutes < 10 ? '0' + minutes : minutes;
    let formatSeconds = seconds < 10 ? '0' + seconds : seconds;

    elemMinutes.innerHTML = formatMinutes
    elemSeconds.innerHTML = formatSeconds
  }

  let timerId = null;
  let endTimestamp = timer.getAttribute('end-time') * 1000
  let endDate = new Date(endTimestamp)

  let elemMinutes = timer.querySelector('[test-elem="minutes"]')
  let elemSeconds = timer.querySelector('[test-elem="seconds"]')

  getServerTimestamp()

  setInterval(getServerTimestamp, 10000);
  timerId = setInterval(countdownTimer, 1000);
}
