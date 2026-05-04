function startConfetti(canvas) {
  const ctx    = canvas.getContext('2d');
  const W      = canvas.width  = window.innerWidth;
  const H      = canvas.height = window.innerHeight;
  const pieces = [];
  const colors = ['#EAB308','#EF4444','#3B82F6','#10B981','#F59E0B','#EC4899','#8B5CF6','#06B6D4'];

  for (let i = 0; i < 180; i++) {
    pieces.push({
      x:   Math.random() * W,
      y:   Math.random() * H - H,
      w:   6 + Math.random() * 10,
      h:   10 + Math.random() * 14,
      r:   Math.random() * Math.PI * 2,
      dr:  (Math.random() - 0.5) * 0.2,
      dx:  (Math.random() - 0.5) * 3,
      dy:  3 + Math.random() * 5,
      c:   colors[Math.floor(Math.random() * colors.length)],
    });
  }

  let frame;
  let done = false;

  function tick() {
    ctx.clearRect(0, 0, W, H);
    let alive = false;
    for (const p of pieces) {
      p.x  += p.dx;
      p.y  += p.dy;
      p.r  += p.dr;
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.r);
      ctx.fillStyle = p.c;
      ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
      ctx.restore();
      if (p.y < H + 20) alive = true;
    }
    if (alive && !done) frame = requestAnimationFrame(tick);
  }

  tick();

  // 5 sn sonra durdur
  setTimeout(() => {
    done = true;
    cancelAnimationFrame(frame);
    ctx.clearRect(0, 0, W, H);
  }, 5000);
}
