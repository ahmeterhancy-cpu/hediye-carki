class Wheel {
  constructor(canvas, prizes) {
    this.canvas   = canvas;
    this.ctx      = canvas.getContext('2d');
    this.prizes   = prizes;
    this.rotation = 0;
    this.spinning = false;
    this.draw();
  }

  draw() {
    const ctx     = this.ctx;
    const { width, height } = this.canvas;
    const cx      = width / 2;
    const cy      = height / 2;
    const radius  = Math.min(cx, cy) - 10;
    const count   = this.prizes.length;
    const slice   = (2 * Math.PI) / count;

    ctx.clearRect(0, 0, width, height);

    // Dış gölge
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,0.4)';
    ctx.shadowBlur  = 20;
    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, 2 * Math.PI);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.restore();

    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate((this.rotation * Math.PI) / 180);

    this.prizes.forEach((p, i) => {
      const startAngle = i * slice;
      const endAngle   = (i + 1) * slice;

      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.arc(0, 0, radius, startAngle, endAngle);
      ctx.fillStyle = p.color_hex || '#FFB400';
      ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,0.6)';
      ctx.lineWidth   = 2;
      ctx.stroke();

      // Metin
      ctx.save();
      ctx.rotate(startAngle + slice / 2);
      ctx.textAlign    = 'right';
      ctx.fillStyle    = '#fff';
      ctx.shadowColor  = 'rgba(0,0,0,0.5)';
      ctx.shadowBlur   = 3;
      ctx.font         = `bold ${count > 8 ? 13 : 16}px sans-serif`;
      const label      = (p.brand_name || p.name || '').substring(0, 14);
      ctx.fillText(label, radius - 14, 5);
      ctx.restore();
    });

    // Merkez daire
    ctx.beginPath();
    ctx.arc(0, 0, 28, 0, 2 * Math.PI);
    ctx.fillStyle   = '#fff';
    ctx.shadowColor = 'rgba(0,0,0,0.3)';
    ctx.shadowBlur  = 8;
    ctx.fill();
    ctx.strokeStyle = '#E5E7EB';
    ctx.lineWidth   = 2;
    ctx.stroke();

    ctx.restore();
  }

  async spinTo(targetAngle, duration = 5000) {
    if (this.spinning) return;
    this.spinning = true;

    const start         = performance.now();
    const startRotation = this.rotation;

    return new Promise(resolve => {
      const animate = (now) => {
        const elapsed = now - start;
        const t       = Math.min(elapsed / duration, 1);
        const eased   = 1 - Math.pow(1 - t, 3);
        this.rotation = startRotation + (targetAngle - startRotation) * eased;
        this.draw();

        if (t < 1) {
          requestAnimationFrame(animate);
        } else {
          this.spinning = false;
          resolve();
        }
      };
      requestAnimationFrame(animate);
    });
  }
}
