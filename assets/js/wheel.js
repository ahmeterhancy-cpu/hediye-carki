/**
 * Çarkıfelek — gerçek görünümlü Canvas çark.
 * - Konik gradient dilimler
 * - Beyaz altın separator çizgiler
 * - Dış kenar LED noktaları (animasyonlu)
 * - Metalik hub (merkez)
 * - Outlined text (siyah stroke + beyaz fill)
 */
class Wheel {
  constructor(canvas, prizes) {
    this.canvas    = canvas;
    this.ctx       = canvas.getContext('2d');
    this.prizes    = prizes;
    this.rotation  = 0;
    this.spinning  = false;
    this.ledPhase  = 0;
    this.draw();
    this.animateLeds();
  }

  draw() {
    const ctx     = this.ctx;
    const W       = this.canvas.width;
    const H       = this.canvas.height;
    const cx      = W / 2;
    const cy      = H / 2;
    const radius  = Math.min(cx, cy) - 28;
    const count   = this.prizes.length;
    const slice   = (2 * Math.PI) / count;

    ctx.clearRect(0, 0, W, H);

    // ── Dış halka (altın gradient) ──────────────────────────────
    const ringGrad = ctx.createRadialGradient(cx, cy, radius - 4, cx, cy, radius + 22);
    ringGrad.addColorStop(0,    '#7a4f00');
    ringGrad.addColorStop(0.45, '#FFD86B');
    ringGrad.addColorStop(0.55, '#FFC107');
    ringGrad.addColorStop(1,    '#5c3a00');
    ctx.beginPath();
    ctx.arc(cx, cy, radius + 22, 0, 2 * Math.PI);
    ctx.fillStyle = ringGrad;
    ctx.fill();

    // İç gölge (halka altında)
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,0.6)';
    ctx.shadowBlur  = 14;
    ctx.beginPath();
    ctx.arc(cx, cy, radius + 4, 0, 2 * Math.PI);
    ctx.fillStyle = '#1a1a1a';
    ctx.fill();
    ctx.restore();

    // ── Dilimler ────────────────────────────────────────────────
    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate((this.rotation * Math.PI) / 180);

    this.prizes.forEach((p, i) => {
      const startAngle = i * slice;
      const endAngle   = (i + 1) * slice;
      const baseColor  = p.color_hex || '#FFB400';

      // Radial gradient: koyu kenardan parlak merkeze
      const grad = ctx.createRadialGradient(0, 0, radius * 0.15, 0, 0, radius);
      grad.addColorStop(0,    this.lighten(baseColor, 0.25));
      grad.addColorStop(0.85, baseColor);
      grad.addColorStop(1,    this.darken(baseColor, 0.20));

      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.arc(0, 0, radius, startAngle, endAngle);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();

      // Beyaz altın separator
      ctx.strokeStyle = '#FFE9A0';
      ctx.lineWidth   = 3;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.lineTo(Math.cos(startAngle) * radius, Math.sin(startAngle) * radius);
      ctx.stroke();
    });

    // ── Text (outlined, dilim merkezine yatay-radial) ──────────
    this.prizes.forEach((p, i) => {
      const startAngle = i * slice;
      const midAngle   = startAngle + slice / 2;

      ctx.save();
      ctx.rotate(midAngle);
      ctx.textAlign    = 'right';
      ctx.textBaseline = 'middle';
      ctx.font         = `900 ${count > 10 ? 14 : count > 8 ? 17 : 20}px system-ui, sans-serif`;

      const label = (p.brand_name || p.name || '').toString().substring(0, 16);

      // Siyah outline
      ctx.strokeStyle = 'rgba(0,0,0,0.85)';
      ctx.lineWidth   = 4;
      ctx.lineJoin    = 'round';
      ctx.strokeText(label, radius - 18, 0);

      // Beyaz fill
      ctx.fillStyle = '#fff';
      ctx.fillText(label, radius - 18, 0);

      ctx.restore();
    });

    ctx.restore();

    // ── LED noktaları (dış kenar) ───────────────────────────────
    const ledCount = 36;
    for (let i = 0; i < ledCount; i++) {
      const angle = (i / ledCount) * 2 * Math.PI;
      const lx = cx + Math.cos(angle) * (radius + 13);
      const ly = cy + Math.sin(angle) * (radius + 13);
      const isOn = (i + this.ledPhase) % 3 === 0;

      ctx.beginPath();
      ctx.arc(lx, ly, isOn ? 5 : 3.5, 0, 2 * Math.PI);
      ctx.fillStyle = isOn ? '#FFFBEA' : '#8A6A1A';
      ctx.shadowColor = isOn ? 'rgba(255, 235, 100, 0.95)' : 'transparent';
      ctx.shadowBlur  = isOn ? 14 : 0;
      ctx.fill();
      ctx.shadowBlur  = 0;
    }

    // ── Metalik merkez hub ──────────────────────────────────────
    const hubR = radius * 0.13;

    // Hub gölgesi
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,0.5)';
    ctx.shadowBlur  = 14;
    ctx.beginPath();
    ctx.arc(cx, cy, hubR + 6, 0, 2 * Math.PI);
    ctx.fillStyle = '#5c3a00';
    ctx.fill();
    ctx.restore();

    // Hub altın
    const hubGrad = ctx.createRadialGradient(cx - hubR/3, cy - hubR/3, 0, cx, cy, hubR + 6);
    hubGrad.addColorStop(0,    '#FFF4C2');
    hubGrad.addColorStop(0.5,  '#FFC107');
    hubGrad.addColorStop(1,    '#7a4f00');
    ctx.beginPath();
    ctx.arc(cx, cy, hubR + 6, 0, 2 * Math.PI);
    ctx.fillStyle = hubGrad;
    ctx.fill();

    // Hub iç beyaz
    ctx.beginPath();
    ctx.arc(cx, cy, hubR, 0, 2 * Math.PI);
    ctx.fillStyle = '#fff';
    ctx.fill();

    // Hub iç parlama
    const sheen = ctx.createRadialGradient(cx - hubR/2, cy - hubR/2, 0, cx, cy, hubR);
    sheen.addColorStop(0,   'rgba(255,255,255,0.95)');
    sheen.addColorStop(0.6, 'rgba(255,255,255,0.0)');
    ctx.beginPath();
    ctx.arc(cx, cy, hubR, 0, 2 * Math.PI);
    ctx.fillStyle = sheen;
    ctx.fill();

    // Hub kenar çizgisi
    ctx.beginPath();
    ctx.arc(cx, cy, hubR, 0, 2 * Math.PI);
    ctx.strokeStyle = 'rgba(0,0,0,0.15)';
    ctx.lineWidth = 1;
    ctx.stroke();
  }

  // LED'leri sürekli animate et (idle ışıltı)
  animateLeds() {
    let last = performance.now();
    const tick = (now) => {
      if (now - last > 200) {
        this.ledPhase = (this.ledPhase + 1) % 3;
        last = now;
        if (!this.spinning) this.draw();
      }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
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
        // Daha doğal yavaşlama: ease-out quintic
        const eased   = 1 - Math.pow(1 - t, 5);
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

  // Renk tonlama yardımcıları
  lighten(hex, amt) {
    const c = this.hexToRgb(hex);
    return `rgb(${Math.min(255, c.r + 255*amt)|0}, ${Math.min(255, c.g + 255*amt)|0}, ${Math.min(255, c.b + 255*amt)|0})`;
  }
  darken(hex, amt) {
    const c = this.hexToRgb(hex);
    return `rgb(${Math.max(0, c.r - 255*amt)|0}, ${Math.max(0, c.g - 255*amt)|0}, ${Math.max(0, c.b - 255*amt)|0})`;
  }
  hexToRgb(hex) {
    const h = hex.replace('#','');
    const n = parseInt(h.length === 3
      ? h.split('').map(c=>c+c).join('')
      : h, 16);
    return { r: (n>>16)&255, g: (n>>8)&255, b: n&255 };
  }
}
