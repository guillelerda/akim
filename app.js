// akim · para comercios — in-memory store (localStorage for persistence)

const STORAGE_KEY = 'akim_data';

function loadData() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) return JSON.parse(raw);
  } catch (_) {}
  return { productos: [], clientes: [], ventas: [], nextId: 1 };
}

function saveData() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
}

function nextId() {
  const id = store.nextId || 1;
  store.nextId = id + 1;
  return id;
}

// Escape HTML to prevent XSS when inserting user content into innerHTML
function esc(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

const store = loadData();

// ── Navigation ──────────────────────────────────────────────────────────────

document.querySelectorAll('.nav-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.section).classList.add('active');
    if (btn.dataset.section === 'dashboard') renderDashboard();
    if (btn.dataset.section === 'ventas') populateVentaSelects();
  });
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatPrice(n) {
  return '$' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function today() {
  return new Date().toLocaleDateString('es-AR');
}

function emptyRow(cols, msg) {
  return `<tr class="empty-row"><td colspan="${cols}">${msg}</td></tr>`;
}

// ── Productos ────────────────────────────────────────────────────────────────

function renderProductos() {
  const tbody = document.querySelector('#tabla-productos tbody');
  if (!store.productos.length) {
    tbody.innerHTML = emptyRow(4, 'Sin productos todavía');
    return;
  }
  tbody.innerHTML = store.productos.map(p => `
    <tr>
      <td>${esc(p.nombre)}</td>
      <td>${esc(formatPrice(p.precio))}</td>
      <td>${esc(p.stock)}</td>
      <td><button class="btn-danger" data-action="del-producto" data-id="${esc(p.id)}">Eliminar</button></td>
    </tr>`).join('');
}

document.getElementById('form-producto').addEventListener('submit', e => {
  e.preventDefault();
  const nombre = document.getElementById('prod-nombre').value.trim();
  const precio = parseFloat(document.getElementById('prod-precio').value);
  const stock = parseInt(document.getElementById('prod-stock').value, 10);
  if (!nombre || isNaN(precio) || isNaN(stock)) return;
  store.productos.push({ id: nextId(), nombre, precio, stock });
  saveData();
  e.target.reset();
  renderProductos();
});

document.querySelector('#tabla-productos').addEventListener('click', e => {
  const btn = e.target.closest('[data-action="del-producto"]');
  if (!btn) return;
  const id = parseInt(btn.dataset.id, 10);
  store.productos = store.productos.filter(p => p.id !== id);
  saveData();
  renderProductos();
});

// ── Clientes ─────────────────────────────────────────────────────────────────

function renderClientes() {
  const tbody = document.querySelector('#tabla-clientes tbody');
  if (!store.clientes.length) {
    tbody.innerHTML = emptyRow(4, 'Sin clientes todavía');
    return;
  }
  tbody.innerHTML = store.clientes.map(c => `
    <tr>
      <td>${esc(c.nombre)}</td>
      <td>${esc(c.email || '—')}</td>
      <td>${esc(c.tel || '—')}</td>
      <td><button class="btn-danger" data-action="del-cliente" data-id="${esc(c.id)}">Eliminar</button></td>
    </tr>`).join('');
}

document.getElementById('form-cliente').addEventListener('submit', e => {
  e.preventDefault();
  const nombre = document.getElementById('cliente-nombre').value.trim();
  const email = document.getElementById('cliente-email').value.trim();
  const tel = document.getElementById('cliente-tel').value.trim();
  if (!nombre) return;
  store.clientes.push({ id: nextId(), nombre, email, tel });
  saveData();
  e.target.reset();
  renderClientes();
});

document.querySelector('#tabla-clientes').addEventListener('click', e => {
  const btn = e.target.closest('[data-action="del-cliente"]');
  if (!btn) return;
  const id = parseInt(btn.dataset.id, 10);
  store.clientes = store.clientes.filter(c => c.id !== id);
  saveData();
  renderClientes();
});

// ── Ventas ────────────────────────────────────────────────────────────────────

function populateVentaSelects() {
  const selCliente = document.getElementById('venta-cliente');
  const selProducto = document.getElementById('venta-producto');
  selCliente.innerHTML = '<option value="">Seleccionar cliente</option>' +
    store.clientes.map(c => `<option value="${esc(c.id)}">${esc(c.nombre)}</option>`).join('');
  selProducto.innerHTML = '<option value="">Seleccionar producto</option>' +
    store.productos.map(p => `<option value="${esc(p.id)}">${esc(p.nombre)} (stock: ${esc(p.stock)})</option>`).join('');
}

function renderVentas() {
  const tbody = document.querySelector('#tabla-ventas tbody');
  if (!store.ventas.length) {
    tbody.innerHTML = emptyRow(5, 'Sin ventas todavía');
    return;
  }
  tbody.innerHTML = [...store.ventas].reverse().map(v => `
    <tr>
      <td>${esc(v.fecha)}</td>
      <td>${esc(v.cliente)}</td>
      <td>${esc(v.producto)}</td>
      <td>${esc(v.cantidad)}</td>
      <td>${esc(formatPrice(v.total))}</td>
    </tr>`).join('');
}

document.getElementById('form-venta').addEventListener('submit', e => {
  e.preventDefault();
  const ci = document.getElementById('venta-cliente').value;
  const pi = document.getElementById('venta-producto').value;
  const cantidad = parseInt(document.getElementById('venta-cantidad').value, 10);
  if (ci === '' || pi === '' || isNaN(cantidad) || cantidad <= 0) {
    alert('Completá todos los campos');
    return;
  }
  const clienteId = parseInt(ci, 10);
  const productoId = parseInt(pi, 10);
  const prod = store.productos.find(p => p.id === productoId);
  const cliente = store.clientes.find(c => c.id === clienteId);
  if (!prod || !cliente) {
    alert('Producto o cliente no encontrado');
    return;
  }
  if (prod.stock < cantidad) {
    alert(`Stock insuficiente. Disponible: ${prod.stock}`);
    return;
  }
  prod.stock -= cantidad;
  store.ventas.push({
    fecha: today(),
    cliente: cliente.nombre,
    producto: prod.nombre,
    cantidad,
    total: prod.precio * cantidad,
  });
  saveData();
  e.target.reset();
  renderVentas();
  renderProductos();
});

// ── Dashboard ─────────────────────────────────────────────────────────────────

function renderDashboard() {
  const todayStr = today();
  const ventasHoy = store.ventas.filter(v => v.fecha === todayStr);
  const totalHoy = ventasHoy.reduce((s, v) => s + v.total, 0);
  const totalGeneral = store.ventas.reduce((s, v) => s + v.total, 0);

  document.getElementById('stat-ventas-hoy').textContent = formatPrice(totalHoy);
  document.getElementById('stat-productos').textContent = store.productos.length;
  document.getElementById('stat-clientes').textContent = store.clientes.length;
  document.getElementById('stat-ventas-total').textContent = formatPrice(totalGeneral);

  const tbody = document.querySelector('#tabla-ultimas-ventas tbody');
  const last = [...store.ventas].reverse().slice(0, 5);
  if (!last.length) {
    tbody.innerHTML = emptyRow(4, 'Sin ventas todavía');
    return;
  }
  tbody.innerHTML = last.map(v => `
    <tr>
      <td>${esc(v.fecha)}</td>
      <td>${esc(v.cliente)}</td>
      <td>${esc(v.producto)}</td>
      <td>${esc(formatPrice(v.total))}</td>
    </tr>`).join('');
}

// ── Init ──────────────────────────────────────────────────────────────────────

renderProductos();
renderClientes();
renderVentas();
renderDashboard();
