YUI.add("moodle-availability_integrityadvocate-form",function(n,e){M.availability_integrityadvocate=M.availability_integrityadvocate||{},M.availability_integrityadvocate.form=n.Object(M.core_availability.plugin),M.availability_integrityadvocate.form.initInner=function(e){this.cms=e},M.availability_integrityadvocate.form.getNode=function(e){var a,t,i,l;for(0,this.cms=Array.isArray(this.cms)?this.cms:[],a='<span class="col-form-label p-r-1"> '+M.util.get_string("title","availability_integrityadvocate")+'</span> <span class="availability-group form-group"><label><span class="accesshide">'+M.util.get_string("label_cm","availability_integrityadvocate")+' </span><select class="custom-select" name="cm" title="'+M.util.get_string("label_cm","availability_integrityadvocate")+'"><option value="0">'+M.util.get_string("choosedots","moodle")+"</option>",i=0;i<this.cms.length;i++)a+='<option value="'+(t=this.cms[i]).id+'">'+t.name+"</option>";return a+='</select></label> <label><span class="accesshide">'+M.util.get_string("label_completion","availability_integrityadvocate")+' </span><select class="custom-select" name="e" title="'+M.util.get_string("label_completion","availability_integrityadvocate")+'"><option value="1">'+M.util.get_string("option_valid","availability_integrityadvocate")+'</option><option value="0">'+M.util.get_string("option_invalid","availability_integrityadvocate")+"</option></select></label></span>",l=n.Node.create('<span class="form-inline">'+a+"</span>"),e.cm!==undefined&&l.one("select[name=cm] > option[value="+e.cm+"]")&&l.one("select[name=cm]").set("value",""+e.cm),e.e!==undefined&&l.one("select[name=e]").set("value",""+e.e),M.availability_integrityadvocate.form.addedEvents||(M.availability_integrityadvocate.form.addedEvents=!0,n.one(".availability-field").delegate("change",function(){M.core_availability.form.update()},".availability_integrityadvocate select")),l},M.availability_integrityadvocate.form.fillValue=function(e,a){e.cm=parseInt(a.one("select[name=cm]").get("value"),10),e.e=parseInt(a.one("select[name=e]").get("value"),10)},M.availability_integrityadvocate.form.fillErrors=function(e,a){0===parseInt(a.one("select[name=cm]").get("value"),10)&&e.push("availability_integrityadvocate:error_selectcmid"),parseInt(a.one("select[name=e]").get("value"),10)}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});