(function(){
  function debounce(fn, wait){
    var t; return function(){
      var ctx=this, args=arguments; clearTimeout(t);
      t=setTimeout(function(){ fn.apply(ctx,args); }, wait);
    };
  }

  function onUpdateVariationValues(){
    var forms=document.querySelectorAll('form.variations_form[data-trigger-stock-check="true"]');
    // TODO: compute unavailable states based on Woo events and selections.
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.body.addEventListener('woocommerce_update_variation_values', debounce(onUpdateVariationValues, 300));
  });
})();
