import{m as y}from"./helpers-DnBipkP3.js";const v=l=>{let r=!1;const s={get value(){return r}},n=e=>e,c=[{name:"Sales",data:[310,440,430,400,700,810,700,770,625,470,470,530]},{name:"Revenue",data:[540,630,570,700,825,500,370,480,750,810,650,760]}],f=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],p=n(()=>({legend:{show:!1},colors:["#465FFF","#9CB9FF"],chart:{fontFamily:"Outfit, sans-serif",height:310,type:"area",toolbar:{show:!1}},stroke:{curve:"straight",width:[2,2]},fill:{type:"gradient",gradient:{opacityFrom:.55,opacityTo:0}},markers:{size:5,colors:["#ffffff"],strokeColors:["#465FFF","#9CB9FF"],strokeWidth:2,hover:{size:7}},grid:{position:"back",borderColor:s.value?"#1E2640":"#F3F4F6",strokeDashArray:0,xaxis:{lines:{show:!1}},yaxis:{lines:{show:!0}}},dataLabels:{enabled:!1},tooltip:{enabled:!0,shared:!0,intersect:!1,custom:function({series:e,dataPointIndex:t,w:a}){const d=a.globals?.categoryLabels?.[t]??a.config.xaxis?.categories?.[t]??"",u=a.config.colors??["#465FFF","#9CB9FF"],g=(a.config.series??[]).map((x,i)=>{const o=e[i]?.[t],m=u[i]??"#465FFF",F=typeof o=="number"?o.toLocaleString():o??"";return`
                  <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                      <span class="size-1.5 rounded-full shrink-0" style="background-color: ${m}"></span>
                      <span class="w-13 text-xs text-gray-800 dark:text-white/90">${x.name??""}</span>
                      <span class="text-xs font-medium text-gray-400 dark:text-gray-500">:</span>
                    </div>
                    <span class="text-xs font-medium text-gray-800 dark:text-white/90">${F}</span>
                  </div>
                `}).join("");return`
              <div class="min-w-32.5">
                <p class="mb-1 text-[10px] leading-4 text-gray-500 dark:text-gray-400">${d}</p>
                <div class="flex flex-col gap-1">
                  ${g}
                </div>
              </div>
            `}},xaxis:{type:"category",categories:f,axisBorder:{show:!1},axisTicks:{show:!1},crosshairs:{show:!0,width:2,position:"back",opacity:1,stroke:{width:0,color:"none"},fill:{type:"gradient",gradient:{colorFrom:s.value?"rgba(255, 255, 255, 0.1)":"#ffffff",colorTo:"#9CB9FF",stops:[0,100],opacityFrom:1,opacityTo:1}}},labels:{style:{fontSize:"12px",fontFamily:"Outfit, sans-serif",colors:s.value?"#94A3B8":"#6B7280"}},tooltip:{enabled:!1}},yaxis:{min:0,max:1e3,tickAmount:5,labels:{formatter:e=>e.toLocaleString(),style:{fontSize:"12px",fontFamily:"Outfit, sans-serif",colors:s.value?"#94A3B8":"#6B7280"}},title:{text:void 0}}}));y(l,e=>{r=e;const t=p();return{...t,series:c,chart:{...t.chart,type:"area",height:310}}})};export{v as default};
