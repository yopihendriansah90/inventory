import{m as h}from"./helpers-DnBipkP3.js";const b=l=>{let r=!1;const s={get value(){return r}},n=e=>e,c=[{name:"Sales",data:[8e3,13e3,7e3,6500,3500,7500,6e3,18500,12e3,4e3,9500,7500]}],f=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],p=n(()=>({legend:{show:!1},colors:["#465FFF"],chart:{fontFamily:"Outfit, sans-serif",height:310,type:"area",toolbar:{show:!1}},stroke:{curve:"smooth",width:2},fill:{type:"gradient",gradient:{opacityFrom:.55,opacityTo:0}},markers:{size:0,colors:["#ffffff"],strokeColors:["#465FFF"],strokeWidth:2,hover:{size:5}},grid:{position:"back",borderColor:s.value?"#1E2640":"#F3F4F6",strokeDashArray:0,xaxis:{lines:{show:!1}},yaxis:{lines:{show:!0}}},dataLabels:{enabled:!1},tooltip:{enabled:!0,shared:!0,intersect:!1,custom:function({series:e,dataPointIndex:t,w:o}){const d=o.globals?.categoryLabels?.[t]??o.config.xaxis?.categories?.[t]??"",u=o.config.colors??["#465FFF"],x=(o.config.series??[]).map((g,i)=>{const a=e[i]?.[t],m=u[i]??"#465FFF",y=typeof a=="number"?a.toLocaleString():a??"";return`
                  <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                      <span class="size-1.5 rounded-full shrink-0" style="background-color: ${m}"></span>
                      <span class="w-13 text-xs text-gray-800 dark:text-white/90">${g.name??""}</span>
                      <span class="text-xs font-medium text-gray-400 dark:text-gray-500">:</span>
                    </div>
                    <span class="text-xs font-medium text-gray-800 dark:text-white/90">${y}</span>
                  </div>
                `}).join("");return`
              <div class="min-w-32.5">
                <p class="mb-1 text-[10px] leading-4 text-gray-500 dark:text-gray-400">${d}</p>
                <div class="flex flex-col gap-1">
                  ${x}
                </div>
              </div>
            `}},xaxis:{type:"category",categories:f,axisBorder:{show:!1},axisTicks:{show:!1},crosshairs:{show:!0,width:2,position:"back",opacity:1,stroke:{width:0,color:"none"},fill:{type:"gradient",gradient:{colorFrom:s.value?"rgba(255, 255, 255, 0.1)":"#ffffff",colorTo:"#9CB9FF",stops:[0,100],opacityFrom:1,opacityTo:1}}},labels:{style:{fontSize:"12px",fontFamily:"Outfit, sans-serif",colors:s.value?"#94A3B8":"#6B7280"}},tooltip:{enabled:!1}},yaxis:{min:0,max:2e4,tickAmount:4,labels:{formatter:e=>e===0?"0":`${(e/1e3).toFixed(0)}K`,style:{fontSize:"12px",fontFamily:"Outfit, sans-serif",colors:s.value?"#94A3B8":"#6B7280"}},title:{text:void 0}}}));h(l,e=>{r=e;const t=p();return{...t,series:c,chart:{...t.chart,type:"area",height:310}}})};export{b as default};
