(() => {
  const root=document.querySelector('[data-packagist-publisher]');if(!root)return;
  const dictionary=JSON.parse(document.body.dataset.ui||'{}');const text=value=>dictionary[value]||value;
  root.addEventListener('click',async event=>{const button=event.target.closest('[data-publish-package]');if(!button||button.disabled)return;const status=button.closest('.list-row').querySelector('[data-publish-status]');button.disabled=true;status.textContent=text('Submitting package…');
    try { const form=new URLSearchParams({_token:root.dataset.token,package:button.dataset.publishPackage});const response=await fetch('/admin/integrations/packagist/publish',{method:'POST',credentials:'same-origin',cache:'no-store',redirect:'error',headers:{Accept:'application/json','Content-Type':'application/x-www-form-urlencoded'},body:form});const result=await response.json();status.textContent=text(result.message||'Package submission could not be completed.');if(!response.ok||result.state==='failed')button.disabled=false; }
    catch {status.textContent=text('Package submission could not be completed.');button.disabled=false;}
  });
})();
