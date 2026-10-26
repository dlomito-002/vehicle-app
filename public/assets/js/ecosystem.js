/* Ecosistema Carrousel · comportamiento visual compartido
 * @version 1.2.1 (misma versión que ecosystem.css; historial en docs/ECOSISTEMA.md)
 * Archivo IDÉNTICO en todas las apps. Solo presentación: no envía datos ni cambia lógica.
 */
(()=>{'use strict';
const desktop=window.matchMedia('(min-width:761px)');

// Tablas más anchas que su contenedor: activa la columna de acciones fija (ecosystem.css §3).
function markOverflowingTables(){
  document.querySelectorAll('.data-table-wrap').forEach(w=>w.classList.toggle('is-overflowing',w.scrollWidth-w.clientWidth>8));
}

// "Más acciones" dentro de una tabla desplazable: el panel se abre fijo sobre la pantalla
// para que no lo recorte el contenedor.
function placePanel(details){
  const panel=details.querySelector('.row-action-disclosure-panel');
  const summary=details.querySelector('summary');
  if(!panel||!summary)return;
  if(!details.open||!desktop.matches){panel.classList.remove('is-floating');panel.style.top=panel.style.left='';return;}
  panel.classList.add('is-floating');
  const r=summary.getBoundingClientRect();
  const w=panel.offsetWidth,h=panel.offsetHeight;
  const below=r.bottom+6+h<=window.innerHeight;
  panel.style.top=Math.max(8,below?r.bottom+6:r.top-6-h)+'px';
  panel.style.left=Math.max(8,Math.min(window.innerWidth-w-8,r.right-w))+'px';
}
function closeFloatingPanels(){
  document.querySelectorAll('.data-table .row-action-disclosure[open]').forEach(d=>{d.open=false;placePanel(d);});
}

document.addEventListener('toggle',e=>{
  const d=e.target;
  if(d instanceof HTMLDetailsElement&&d.matches('.data-table .row-action-disclosure'))placePanel(d);
},true);
document.querySelectorAll('.data-table-wrap').forEach(w=>w.addEventListener('scroll',closeFloatingPanels,{passive:true}));
window.addEventListener('scroll',closeFloatingPanels,{passive:true});
window.addEventListener('resize',()=>{markOverflowingTables();closeFloatingPanels();});
markOverflowingTables();
// Algunas apps normalizan tablas después de cargar; se recalcula cuando todo terminó.
window.addEventListener('load',markOverflowingTables);

// Botón de tema del ecosistema (login y demás pantallas que lo usen): alterna claro/oscuro.
// Misma clave en todas las apps, así el modo elegido se respeta al pasar de una a otra.
document.addEventListener('click',e=>{
  const btn=e.target instanceof Element?e.target.closest('[data-eco-theme-toggle]'):null;
  if(!btn)return;
  const next=document.documentElement.dataset.theme==='dark'?'light':'dark';
  document.documentElement.dataset.theme=next;
  document.documentElement.style.colorScheme=next;
  try{localStorage.setItem('carrousel-theme',next);}catch(err){}
});
})();
