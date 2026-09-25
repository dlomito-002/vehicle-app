import './bootstrap';

const maximumImageDimension = 1920;
const imageQuality = 0.8;

const compressImage = (file) => new Promise((resolve, reject) => {
	const image = new Image();
	const objectUrl = URL.createObjectURL(file);

	image.onload = () => {
		const scale = Math.min(1, maximumImageDimension / Math.max(image.width, image.height));
		const canvas = document.createElement('canvas');
		canvas.width = Math.max(1, Math.round(image.width * scale));
		canvas.height = Math.max(1, Math.round(image.height * scale));
		canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
		canvas.toBlob((blob) => {
			URL.revokeObjectURL(objectUrl);
			if (!blob) {
				reject(new Error('No se pudo preparar una fotografía.'));
				return;
			}

			resolve(new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, {
				type: 'image/jpeg',
				lastModified: file.lastModified,
			}));
		}, 'image/jpeg', imageQuality);
	};
	image.onerror = () => {
		URL.revokeObjectURL(objectUrl);
		reject(new Error('No se pudo leer una fotografía.'));
	};
	image.src = objectUrl;
});

const compressFormImages = (form) => {
	const fileInputs = [...form.querySelectorAll('input[type="file"]:not([data-skip-compress])')];
	const selectedFileInputs = fileInputs.filter((input) => input.files.length);

	return Promise.all(selectedFileInputs.map(async (input) => {
		const compressedFiles = await Promise.all([...input.files].map(compressImage));
		const dataTransfer = new DataTransfer();
		compressedFiles.forEach((file) => dataTransfer.items.add(file));
		input.files = dataTransfer.files;
	}));
};

// Single entry point for submitting the multi-step vehicle forms. Validation,
// image compression and the actual submit all happen here, exactly once, so a
// double click (or compression finishing late) can never POST the form twice.
window.submitFormOnce = async (form) => {
	if (form.dataset.submitting === 'true' || !form.reportValidity()) {
		return;
	}

	form.dataset.submitting = 'true';
	const submitButton = form.querySelector('button[type="submit"]');
	submitButton?.setAttribute('disabled', 'disabled');

	try {
		if (form.hasAttribute('data-compress-images')) {
			await compressFormImages(form);
		}
	} catch (error) {
		delete form.dataset.submitting;
		submitButton?.removeAttribute('disabled');
		window.alert(error.message);
		return;
	}

	// Fields on the inactive step are disabled so they skip validation above;
	// re-enable them so their values are included in the submission.
	form.querySelectorAll('fieldset').forEach((fieldset) => {
		fieldset.disabled = false;
	});
	form.submit();
};

// Restore the forms if the user comes back via the browser's back button.
window.addEventListener('pageshow', (event) => {
	if (!event.persisted) {
		return;
	}

	document.querySelectorAll('form[data-submitting]').forEach((form) => {
		delete form.dataset.submitting;
		form.querySelector('button[type="submit"]')?.removeAttribute('disabled');
	});
});


/* Select buscable global - inspirado en la experiencia de PayOutParques */
(function(){
  'use strict';

  const states=new WeakMap();
  const openStates=new Set();
  let sequence=0;

  const normalize=value=>String(value??'')
    .toLocaleLowerCase('es-GT')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ')
    .trim();

  function visibleButtons(state){
    return state.optionButtons.filter(button=>!button.hidden&&!button.disabled);
  }

  function syncFromNative(state){
    const selected=state.select.selectedOptions[0]||state.select.options[0]||null;
    const text=selected?.textContent?.trim()||'Selecciona una opción';
    state.value.textContent=text;
    state.value.classList.toggle('is-placeholder',!selected||selected.value==='');
    state.trigger.disabled=state.select.disabled;
    state.wrapper.classList.toggle('is-disabled',state.select.disabled);
    state.wrapper.classList.toggle('is-invalid',!state.select.validity.valid);
    state.optionButtons.forEach(button=>{
      const active=selected&&button.dataset.value===selected.value;
      button.setAttribute('aria-selected',active?'true':'false');
    });
  }

  function positionMenu(state){
    if(!state.menu.classList.contains('is-open'))return;
    const rect=state.trigger.getBoundingClientRect();
    const margin=8;
    const width=Math.min(Math.max(rect.width,220),window.innerWidth-margin*2);
    let left=Math.min(Math.max(rect.left,margin),window.innerWidth-width-margin);
    state.menu.style.width=`${width}px`;
    state.menu.style.left=`${left}px`;
    state.menu.style.top=`${Math.min(rect.bottom+6,window.innerHeight-margin)}px`;
    const height=state.menu.offsetHeight;
    const roomBelow=window.innerHeight-rect.bottom-margin;
    const roomAbove=rect.top-margin;
    if(height>roomBelow&&roomAbove>roomBelow){
      state.menu.style.top=`${Math.max(margin,rect.top-height-6)}px`;
    }
  }

  function setActive(state,index){
    const buttons=visibleButtons(state);
    state.optionButtons.forEach(button=>button.classList.remove('is-active'));
    if(buttons.length===0){state.activeIndex=-1;return;}
    const normalizedIndex=((index%buttons.length)+buttons.length)%buttons.length;
    state.activeIndex=normalizedIndex;
    const active=buttons[normalizedIndex];
    active.classList.add('is-active');
    active.scrollIntoView({block:'nearest'});
  }

  function filterOptions(state){
    const query=normalize(state.search.value);
    const tokens=query?query.split(' ').filter(Boolean):[];
    let shown=0;
    state.optionButtons.forEach(button=>{
      const haystack=normalize(button.dataset.searchTerms||button.dataset.label||'');
      const show=tokens.length===0||tokens.every(token=>haystack.includes(token));
      button.hidden=!show;
      if(show)shown+=1;
    });
    state.empty.hidden=shown!==0;
    state.activeIndex=-1;
    if(shown>0)setActive(state,0);
  }

  function closeSelect(state,returnFocus=false){
    if(!state.menu.classList.contains('is-open'))return;
    state.menu.classList.remove('is-open');
    state.wrapper.classList.remove('is-open');
    state.trigger.setAttribute('aria-expanded','false');
    openStates.delete(state);
    state.search.value='';
    filterOptions(state);
    if(returnFocus)state.trigger.focus();
  }

  function closeOthers(except=null){
    [...openStates].forEach(state=>{if(state!==except)closeSelect(state);});
  }

  function openSelect(state,focusLast=false){
    if(state.select.disabled)return;
    closeOthers(state);
    syncFromNative(state);
    state.menu.classList.add('is-open');
    state.wrapper.classList.add('is-open');
    state.trigger.setAttribute('aria-expanded','true');
    openStates.add(state);
    state.search.value='';
    filterOptions(state);
    positionMenu(state);
    window.requestAnimationFrame(()=>{
      state.search.focus();
      if(focusLast){const buttons=visibleButtons(state);if(buttons.length)setActive(state,buttons.length-1);}
    });
  }

  function selectOption(state,option){
    if(option.disabled)return;
    const changed=state.select.value!==option.value;
    state.select.value=option.value;
    state.wrapper.classList.remove('is-invalid');
    syncFromNative(state);
    if(changed){
      state.select.dispatchEvent(new Event('input',{bubbles:true}));
      state.select.dispatchEvent(new Event('change',{bubbles:true}));
    }
    closeSelect(state,true);
  }

  function rebuildOptions(state){
    state.options.innerHTML='';
    state.optionButtons=[];
    [...state.select.options].forEach((option,index)=>{
      const button=document.createElement('button');
      button.type='button';
      button.className='smart-select-option';
      button.setAttribute('role','option');
      button.dataset.value=option.value;
      button.dataset.label=option.textContent?.trim()||'';
      const group=option.parentElement instanceof HTMLOptGroupElement?option.parentElement.label:'';
      button.dataset.searchTerms=[
        option.textContent?.trim()||'',
        option.value||'',
        group,
        option.dataset.searchTerms||'',
        option.dataset.categoryHelp||'',
        option.dataset.categoryPlaceholder||''
      ].join(' ');
      button.textContent=option.textContent?.trim()||'—';
      button.disabled=option.disabled;
      button.dataset.optionIndex=String(index);
      button.addEventListener('click',()=>selectOption(state,option));
      state.options.appendChild(button);
      state.optionButtons.push(button);
    });
    state.options.appendChild(state.empty);
    filterOptions(state);
    syncFromNative(state);
  }

  function initSearchableSelect(select){
    if(!(select instanceof HTMLSelectElement))return;
    if(select.multiple||select.size>1||select.dataset.searchableSelect==='off'||states.has(select))return;

    sequence+=1;
    const wrapper=document.createElement('div');
    wrapper.className='smart-select';

    const trigger=document.createElement('button');
    trigger.type='button';
    trigger.className='smart-select-trigger';
    trigger.setAttribute('role','combobox');
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');

    const value=document.createElement('span');
    value.className='smart-select-value';
    const chevron=document.createElement('span');
    chevron.className='smart-select-chevron';
    chevron.setAttribute('aria-hidden','true');
    chevron.textContent='⌄';
    trigger.append(value,chevron);

    const menu=document.createElement('div');
    menu.className='smart-select-menu';
    menu.id=`smart-select-menu-${sequence}`;
    menu.setAttribute('role','presentation');
    trigger.setAttribute('aria-controls',menu.id);

    const searchWrap=document.createElement('div');
    searchWrap.className='smart-select-search-wrap';
    const search=document.createElement('input');
    search.type='search';
    search.className='smart-select-search';
    search.placeholder=select.dataset.searchPlaceholder||'Buscar...';
    search.autocomplete='off';
    search.spellcheck=false;
    search.setAttribute('aria-label','Buscar opción');
    searchWrap.appendChild(search);

    const options=document.createElement('div');
    options.className='smart-select-options';
    options.setAttribute('role','listbox');
    options.setAttribute('aria-label',select.getAttribute('aria-label')||select.name||'Opciones');

    const empty=document.createElement('div');
    empty.className='smart-select-empty';
    empty.textContent='Sin coincidencias';
    empty.hidden=true;

    menu.append(searchWrap,options);
    select.insertAdjacentElement('afterend',wrapper);
    wrapper.appendChild(trigger);
    document.body.appendChild(menu);
    select.classList.add('smart-select-native');

    const state={select,wrapper,trigger,value,menu,search,options,empty,optionButtons:[],activeIndex:-1};
    states.set(select,state);

    trigger.addEventListener('click',()=>{
      if(menu.classList.contains('is-open'))closeSelect(state);else openSelect(state);
    });
    trigger.addEventListener('keydown',event=>{
      if(event.key==='ArrowDown'){event.preventDefault();openSelect(state,false);}
      else if(event.key==='ArrowUp'){event.preventDefault();openSelect(state,true);}
      else if(event.key==='Enter'||event.key===' '){event.preventDefault();openSelect(state,false);}
      else if(event.key==='Escape'){event.preventDefault();closeSelect(state);}
    });
    search.addEventListener('input',()=>filterOptions(state));
    search.addEventListener('keydown',event=>{
      const buttons=visibleButtons(state);
      if(event.key==='ArrowDown'){
        event.preventDefault();setActive(state,state.activeIndex+1);
      }else if(event.key==='ArrowUp'){
        event.preventDefault();setActive(state,state.activeIndex-1);
      }else if(event.key==='Enter'){
        event.preventDefault();
        const active=buttons[state.activeIndex]||buttons[0];
        if(active){const index=Number(active.dataset.optionIndex);const option=state.select.options[index];if(option)selectOption(state,option);}
      }else if(event.key==='Escape'){
        event.preventDefault();closeSelect(state,true);
      }else if(event.key==='Tab'){
        closeSelect(state);
      }
    });
    select.addEventListener('change',()=>syncFromNative(state));
    select.addEventListener('focus',()=>trigger.focus());
    select.addEventListener('invalid',()=>{
      wrapper.classList.add('is-invalid');
      window.setTimeout(()=>trigger.focus(),0);
    });

    rebuildOptions(state);
  }

  document.querySelectorAll('select').forEach(initSearchableSelect);

  document.addEventListener('click',event=>{
    const target=event.target instanceof Node?event.target:null;
    if(!target)return;
    [...openStates].forEach(state=>{
      if(state.wrapper.contains(target)||state.menu.contains(target))return;
      closeSelect(state);
    });
  });

  document.addEventListener('reset',event=>{
    const form=event.target instanceof HTMLFormElement?event.target:null;
    if(!form)return;
    window.setTimeout(()=>form.querySelectorAll('select').forEach(select=>{
      const state=states.get(select);if(state)syncFromNative(state);
    }),0);
  });

  const reposition=()=>openStates.forEach(positionMenu);
  window.addEventListener('resize',reposition,{passive:true});
  window.addEventListener('scroll',reposition,{passive:true,capture:true});

  const observer=new MutationObserver(mutations=>{
    mutations.forEach(mutation=>{
      if(mutation.type==='childList'){
        mutation.addedNodes.forEach(node=>{
          if(!(node instanceof Element))return;
          if(node.matches('select'))initSearchableSelect(node);
          node.querySelectorAll?.('select').forEach(initSearchableSelect);
        });
        const select=mutation.target instanceof Element?mutation.target.closest('select'):null;
        const state=select?states.get(select):null;
        if(state)rebuildOptions(state);
      }else if(mutation.type==='attributes'){
        const target=mutation.target instanceof Element?mutation.target:null;
        const select=target instanceof HTMLSelectElement?target:target?.closest('select');
        const state=select?states.get(select):null;
        if(state)rebuildOptions(state);
      }
    });
  });
  observer.observe(document.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['disabled','selected']});

  window.CarrouselSearchableSelect=Object.freeze({
    init:initSearchableSelect,
    refresh(select){const state=states.get(select);if(state)rebuildOptions(state);else initSearchableSelect(select);}
  });
})();
