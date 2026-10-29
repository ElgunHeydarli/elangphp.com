(() => {
  const root=document.querySelector('[data-packagist-publisher]');if(!root)return;
  let dictionary={};try{dictionary=JSON.parse(document.body.dataset.ui||'{}')}catch{}
  const text=value=>dictionary[value]||value;let busy=false;
  async function submit(button){const status=button.closest('.list-row').querySelector('[data-publish-status]');button.disabled=true;status.textContent=text('Submitting package…');
    try {const form=new URLSearchParams({_token:root.dataset.token,package:button.dataset.publishPackage});const response=await fetch('/admin/integrations/packagist/publish',{method:'POST',credentials:'same-origin',cache:'no-store',redirect:'error',headers:{Accept:'application/json','Content-Type':'application/x-www-form-urlencoded'},body:form});const result=await response.json();status.textContent=text(result.message||'Package submission could not be completed.');if(!response.ok||result.state==='failed')button.disabled=false;}
    catch{status.textContent=text('Package submission could not be completed.');button.disabled=false;}
  }
  root.addEventListener('click',async event=>{const target=event.target.closest('[data-publish-package],[data-publish-all]');if(!target||target.disabled||busy)return;busy=true;const all=root.querySelector('[data-publish-all]');if(all)all.disabled=true;
    try{if(target.hasAttribute('data-publish-all')){root.querySelector('[data-publish-all-status]').textContent=text('Submitting prepared packages one at a time…');for(const button of root.querySelectorAll('[data-publish-package]:not(:disabled)'))await submit(button);root.querySelector('[data-publish-all-status]').textContent=text('Submission finished. Review each package status.');}else await submit(target);}
    finally{busy=false;if(all)all.disabled=false;}
  });
})();
