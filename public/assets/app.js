const $=id=>document.getElementById(id);
const fmt=(v,s='')=>v===null||v===undefined?'—':`${v}${s}`;
function set(id,value){const el=$(id);if(el)el.textContent=value}
function bars(id,values){const el=$(id);if(!el)return;el.innerHTML=values.map(v=>`<i style="height:${Math.max(8,v)}%"></i>`).join('')}
async function loadMetrics(){
  try{
    const r=await fetch('./api/metrics.php',{cache:'no-store'});
    const d=await r.json();
    set('story',d.story);set('sourceMode',d.live?'LIVE DATA':'DEMO MODE');
    set('updated',new Date(d.generated_at).toLocaleTimeString([],{hour:'numeric',minute:'2-digit'}));
    set('indoor',fmt(d.temperature.indoor_f,'°'));set('upstairs',fmt(d.temperature.upstairs_f,'°'));
    set('outdoor',fmt(d.temperature.outdoor_f,'°'));set('humidity',fmt(d.humidity.indoor_pct,'%'));
    set('setpoint',fmt(d.system.setpoint_f,'°'));set('action',d.system.action.toUpperCase());
    set('runtimeToday',fmt(d.runtime.today_hours,' h'));set('runtime7d',fmt(d.runtime.seven_day_hours,' h'));
    set('runtimeAvg',fmt(d.runtime.daily_average_hours,' h'));set('energyToday',fmt(d.energy.today_kwh,' kWh'));
    set('comfortScore',fmt(d.comfort.score));set('floorGap',fmt(d.temperature.floor_gap_f,'°'));
    set('targetGap',fmt(d.temperature.target_gap_f,'°'));
    const gauge=$('comfortGauge');if(gauge)gauge.style.width=`${d.comfort.score}%`;
    bars('runtimeSpark',[42,58,51,74,63,81,Math.min(100,(d.runtime.today_hours||0)*10)]);
    bars('tempSpark',[54,58,61,66,62,57,52]);
  }catch(e){set('story','ClimateIQ could not reach the live metrics endpoint.');set('sourceMode','OFFLINE')}
}
loadMetrics();setInterval(loadMetrics,60000);
