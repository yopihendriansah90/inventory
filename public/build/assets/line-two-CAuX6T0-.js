import{m as u}from"./helpers-DnBipkP3.js";const k=i=>{let a=!1;const l={get value(){return a}},n=(t=>t)(()=>({legend:{show:!1,position:"top",horizontalAlign:"left"},colors:["#465FFF","#9CB9FF"],chart:{fontFamily:"Outfit, sans-serif",height:310,width:"100%",type:"line",toolbar:{show:!1}},stroke:{curve:"straight",width:[2,2]},fill:{type:"gradient",gradient:{opacityFrom:.55,opacityTo:0}},markers:{size:0,strokeColors:"#fff",strokeWidth:2,hover:{size:6}},grid:{xaxis:{lines:{show:!1}},yaxis:{lines:{show:!0}}},dataLabels:{enabled:!1},tooltip:{enabled:!0,shared:!0,intersect:!1,custom:function({series:t,dataPointIndex:e,w:s}){const p=s.globals?.categoryLabels?.[e]??s.config.xaxis?.categories?.[e]??"",f=s.config.colors??["#465FFF","#9CB9FF"],d=(s.config.series??[]).map((g,r)=>{const o=t[r]?.[e],x=f[r]??"#465FFF",h=typeof o=="number"?o.toLocaleString():o??"";return`
                  <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                      <span class="size-1.5 rounded-full shrink-0" style="background-color: ${x}"></span>
                      <span class="w-13 text-xs text-gray-800 dark:text-white/90">${g.name??""}</span>
                      <span class="text-xs font-medium text-gray-400 dark:text-gray-500">:</span>
                    </div>
                    <span class="text-xs font-medium text-gray-800 dark:text-white/90">${h}</span>
                  </div>
                `}).join("");return`
              <div class="min-w-32.5">
                <p class="mb-1 text-[10px] leading-4 text-gray-500 dark:text-gray-400">${p}</p>
                <div class="flex flex-col gap-1">
                  ${d}
                </div>
              </div>
            `}},xaxis:{type:"category",categories:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],axisBorder:{show:!1},axisTicks:{show:!1},crosshairs:{show:!0,width:2,position:"back",opacity:1,stroke:{width:0,color:"none"},fill:{type:"gradient",gradient:{colorFrom:l.value?"rgba(255, 255, 255, 0.1)":"#ffffff",colorTo:"#9CB9FF",stops:[0,100],opacityFrom:1,opacityTo:1}}},tooltip:{enabled:!1}},yaxis:{labels:{style:{fontSize:"12px",colors:["#6B7280"]}},title:{text:"",style:{fontSize:"0px"}}}})),c=[{name:"Sales",data:[180,190,170,160,175,165,170,205,230,210,240,235]}];u(i,t=>{a=t;const e=n();return{...e,series:c,chart:{...e.chart,type:"area",height:310}}})};export{k as default};
