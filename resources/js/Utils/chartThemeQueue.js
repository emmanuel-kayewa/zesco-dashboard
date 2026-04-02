// Staggered chart-rebuild queue shared by ALL chart components.
// When dark mode toggles, every mounted chart wants to dispose + re-init.
// Doing that synchronously for ~10 charts freezes the UI for seconds.
// This queue processes one chart per animation frame, keeping the page responsive.

const queue = [];
let running = false;

function process() {
  if (!queue.length) {
    running = false;
    return;
  }
  const fn = queue.shift();
  fn();
  requestAnimationFrame(process);
}

export function enqueueThemeRebuild(fn) {
  queue.push(fn);
  if (!running) {
    running = true;
    requestAnimationFrame(process);
  }
}
