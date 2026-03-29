<template>
    <AppLayout
        :directorates="directorates"
        :aiScope="{ type: 'pp_explorer', directorate_id: directorate.id, filters: explorerData.appliedFilters }"
    >
        <template #title>PP Explorer</template>

        <!-- Header only shown when NOT in directorate sidebar mode -->
        <template v-if="!directorateStore.activeDirectorate">
            <Breadcrumb :items="breadcrumbItems" />

            <PageHeader :metrics="headerMetrics">
                <template #left>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                            <template v-if="hasFilters">
                                {{ filterSummaryTitle }}
                            </template>
                            <template v-else>
                                All Projects
                            </template>
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ explorerData.totalCount }} project{{ explorerData.totalCount !== 1 ? 's' : '' }} matching
                            <template v-if="hasFilters">current filters</template>
                            <template v-else>all criteria</template>
                        </p>
                    </div>
                </template>
            </PageHeader>
        </template>

        <!-- Active Filter Chips (hidden on lg+ where sidebar shows them) -->
        <div class="lg:hidden flex flex-wrap items-center gap-2 mb-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Filters:</span>

            <template v-if="hasFilters">
                <span v-for="(val, dim) in explorerData.appliedFilters" :key="dim"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium"
                      :style="filterChipStyle">
                    <span class="text-[10px] uppercase opacity-70">{{ dimensionLabels[dim] || dim }}:</span>
                    {{ val }}
                    <button @click="removeFilter(dim)" class="ml-0.5 hover:text-red-600 transition-colors" title="Remove filter">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </span>
                <button @click="clearAllFilters" class="text-xs text-red-500 hover:text-red-700 font-medium ml-1">
                    Clear All
                </button>
            </template>
            <span v-else class="text-xs text-gray-400 italic">No filters applied</span>

            <!-- Add filter (inline on small screens) -->
            <div class="flex items-center gap-2 mt-1 w-full">
                <Select
                    v-model="addFilterDim"
                    :options="availableDimensionsOptions"
                    placeholder="+ Add Filter"
                    size="sm"
                    class="w-40"
                />
                <Select
                    v-if="addFilterDim"
                    v-model="addFilterVal"
                    :options="addFilterOptions"
                    placeholder="Select value…"
                    size="sm"
                    class="flex-1 max-w-[200px]"
                />
            </div>
        </div>

        <!-- View mode toggle + filter summary (always visible) -->
        <div class="flex items-center justify-between gap-3 mb-6 no-print">
            <!-- Filter summary line (lg only, since chips are in sidebar) -->
            <div class="hidden lg:flex items-center gap-2 min-w-0 flex-1">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide flex-shrink-0">Filters:</span>
                <template v-if="hasFilters">
                    <span v-for="(val, dim) in explorerData.appliedFilters" :key="dim"
                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                          :style="filterChipStyle">
                        <span class="text-[10px] uppercase opacity-70">{{ dimensionLabels[dim] || dim }}:</span>
                        {{ val }}
                    </span>
                </template>
                <span v-else class="text-xs text-gray-400 italic">No filters applied</span>
            </div>

            <!-- View Mode Toggle (small screens only; sidebar has it on lg) -->
            <div class="flex lg:hidden items-center gap-1 bg-gray-100 dark:bg-gray-700/50 rounded-lg p-1 flex-shrink-0">
                <button
                    @click="viewMode = 'classic'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 flex items-center gap-1.5',
                        viewMode === 'classic'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                    title="Classic View"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    <span>Classic</span>
                </button>
                <button
                    @click="viewMode = 'compact'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 flex items-center gap-1.5',
                        viewMode === 'compact'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                    title="Compact View"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>Compact</span>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- ── CLASSIC VIEW (Original Layout) ── -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <template v-if="viewMode === 'classic'">
            <!-- ── Adaptive Breakdown Charts ── -->
            <template v-if="Object.keys(explorerData.breakdowns).length > 0">
                <div class="mb-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Drill Down Further</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Click any chart element to add a filter</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <template v-for="(bd, dim) in explorerData.breakdowns" :key="dim">
                    <!-- Skip lifecycle_phase, energy_type, and already filtered dimensions in Classic View -->
                    <ChartCard v-if="dim !== 'lifecycle_phase' && dim !== 'energy_type' && !bd.isFiltered" :title="`Projects by ${bd.label}`">
                        <template #default="{ zoomedHeight }">
                        <!-- Use 3D pie for sector -->
                        <Pie3DChart
                            v-if="dim === 'sector'"
                            :data="bd.data.map(d => ({ name: d.name, value: d.value }))"
                            :height="zoomedHeight || '300px'"
                            :show-legend="false"
                            @chart-click="(p) => addDimensionFilter(dim, p.name)"
                        />

                        <!-- Province / District: map ↔ bar toggle -->
                        <ZambiaMapChart
                            v-else-if="dim === 'province' || dim === 'district'"
                            :data="bd.data.map(d => ({ name: d.name, value: d.value, investment: d.totalCost || 0 }))"
                            :level="dim"
                            :metricLabel="bd.label"
                            :showToggle="true"
                            :showMetricToggle="true"
                            :height="zoomedHeight || '300px'"
                            @region-click="(name) => addDimensionFilter(dim, name)"
                        >
                            <BarChart
                                :data="bd.data.map(d => ({ label: d.name, value: d.value }))"
                                xField="label"
                                yField="value"
                                :seriesName="bd.label"
                                :colors="pickCategorical(bd.data.length)"
                                :height="zoomedHeight || '300px'"
                                :horizontal="bd.data.length > 5"
                                @chart-click="(p) => addDimensionFilter(dim, p.name || extractBarLabel(p))"
                            />
                        </ZambiaMapChart>

                        <!-- Other dimensions: plain bar -->
                        <BarChart
                            v-else
                            :data="bd.data.map(d => ({ label: d.name, value: d.value }))"
                            xField="label"
                            yField="value"
                            :seriesName="bd.label"
                            :colors="pickCategorical(bd.data.length)"
                            :height="zoomedHeight || '300px'"
                            :horizontal="bd.data.length > 5"
                            @chart-click="(p) => addDimensionFilter(dim, p.name || extractBarLabel(p))"
                        />
                        </template>
                    </ChartCard>
                </template>
                <!-- ── Investment Chart (always shown if sectors vary) ── -->
                <div v-if="explorerData.sectorInvestment.length > 1">
                    <ChartCard title="Investment by Sector — Committed vs Paid (USD)">
                        <template #default="{ zoomedHeight }">
                        <BarChart
                            :data="investmentBarData"
                            xField="label"
                            :multiSeries="investmentSeries"
                            :height="zoomedHeight || '320px'"
                            @chart-click="(p) => addDimensionFilter('sector', p.name)"
                        />
                        </template>
                    </ChartCard>
                </div>
            </div>
        </template>

            

            <!-- ── Grid Impact Studies Banner (shows when IPP sector is filtered) ── -->
        <!-- <div v-if="isIppContext" class="mb-8">
            <Link href="/pp/dashboard/grid-studies"
                  class="block bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl border border-purple-200 dark:border-purple-700/50 p-5 hover:shadow-lg transition-all duration-200 cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                            Grid Impact Studies Tracker
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            View the dedicated 5-stage pipeline tracker for IPP grid connection studies
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </div>
            </Link>
        </div> -->

            <!-- ── Risk Summary (if available) ── -->
            <div v-if="explorerData.risksByCategory.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <ChartCard title="Risks by Category">
                <template #default="{ zoomedHeight }">
                <Pie3DChart
                    :data="riskCategoryData"
                    :height="zoomedHeight || '280px'"
                    :show-legend="false"
                />
                </template>
            </ChartCard>
            <ChartCard title="Risks by Status">
                <template #default="{ zoomedHeight }">
                <BarChart
                    :data="riskStatusData"
                    xField="label"
                    yField="value"
                    seriesName="Risks"
                    :colors="pickCategorical(riskStatusData.length)"
                    :height="zoomedHeight || '280px'"
                />
                </template>
            </ChartCard>
            </div>
        </template>

        <!-- ══════════════════════════════════════════════════════════════════════ -->
        <!-- ── COMPACT VIEW (Power BI Style Layout) ── -->
        <!-- ══════════════════════════════════════════════════════════════════════ -->
        <template v-else-if="viewMode === 'compact'">
            <template v-if="Object.keys(explorerData.breakdowns).length > 0 || explorerData.risksByCategory.length > 0">
                <div class="mb-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Drill Down Further</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Click any chart element to add a filter</p>
                </div>

                <!-- 12-Column Grid Layout (3 cards per row, 4 cols each) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 lg:grid-cols-12 gap-6 mb-8">
                    <!-- Row 1: Projects, Geographic Distribution -->
                    
                    <!-- Projects Card (6 cols / 1/2 width) - Scrollable list -->
                    <div class="col-span-1 sm:col-span-2 md:col-span-6 lg:col-span-6">
                        <Card title="Projects" class="h-full">
                            <template #actions>
                                <button
                                    @click="expandedChart = { type: 'projects', title: 'Projects List' }"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                                    title="View all projects"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>
                            </template>
                            <div class="max-h-[400px] overflow-y-auto">
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ explorerData.projects.length }}</span> project{{ explorerData.projects.length !== 1 ? 's' : '' }}
                                    </p>
                                    <button
                                        @click="expandedChart = { type: 'projects', title: 'Projects List' }"
                                        class="text-sm text-zesco-600 hover:text-zesco-700 dark:text-zesco-400 dark:hover:text-zesco-300 font-medium"
                                    >
                                        View All →
                                    </button>
                                </div>
                                <!-- Preview using ProjectStackedList component -->
                                <ProjectStackedList
                                    :projects="explorerData.projects"
                                    empty-title="No projects found"
                                    empty-body="Try adjusting your filters."
                                    :show-rag="true"
                                    :show-key-issue="false"
                                    :show-progress="false"
                                    @select="goToProject"
                                />
                            </div>
                        </Card>
                    </div>

                    <!-- Province/District Tabbed Card (6 cols / 1/2 width) -->
                    <div v-if="explorerData.breakdowns.province || explorerData.breakdowns.district" class="col-span-1 sm:col-span-2 md:col-span-6 lg:col-span-6">
                        <TabCard
                            v-model="geographicActiveTab"
                            :tabs="geographicTabs"
                            :class="['h-full', activeGeographicFilter && 'ring-2 ring-zesco-400/50']"
                        >
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span>Geographic Distribution</span>
                                    <span v-if="activeGeographicFilter" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zesco-100 text-zesco-700 dark:bg-zesco-900/30 dark:text-zesco-400">
                                        {{ activeGeographicFilter.value }}
                                    </span>
                                </div>
                            </template>
                            <template #actions>
                                <button
                                    @click="expandedChart = { type: 'geographic', title: 'Geographic Distribution' }"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                                    title="Expand chart"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>
                            </template>
                            <template v-if="explorerData.breakdowns.province" #tab-province>
                                <ZambiaMapChart
                                    :data="explorerData.breakdowns.province.data.map(d => ({ name: d.name, value: d.value, investment: d.totalCost || 0 }))"
                                    level="province"
                                    :metricLabel="explorerData.breakdowns.province.label"
                                    :showToggle="true"
                                    :showMetricToggle="true"
                                    height="280px"
                                    @region-click="(name) => addDimensionFilter('province', name)"
                                >
                                    <BarChart
                                        :data="explorerData.breakdowns.province.data.map(d => ({ label: d.name, value: d.value }))"
                                        xField="label"
                                        yField="value"
                                        :seriesName="explorerData.breakdowns.province.label"
                                        :colors="pickCategorical(explorerData.breakdowns.province.data.length)"
                                        height="280px"
                                        :horizontal="explorerData.breakdowns.province.data.length > 5"
                                        @chart-click="(p) => addDimensionFilter('province', p.name || extractBarLabel(p))"
                                    />
                                </ZambiaMapChart>
                            </template>
                            <template v-if="explorerData.breakdowns.district" #tab-district>
                                <ZambiaMapChart
                                    :data="explorerData.breakdowns.district.data.map(d => ({ name: d.name, value: d.value, investment: d.totalCost || 0 }))"
                                    level="district"
                                    :metricLabel="explorerData.breakdowns.district.label"
                                    :showToggle="true"
                                    :showMetricToggle="true"
                                    height="280px"
                                    @region-click="(name) => addDimensionFilter('district', name)"
                                >
                                    <BarChart
                                        :data="explorerData.breakdowns.district.data.map(d => ({ label: d.name, value: d.value }))"
                                        xField="label"
                                        yField="value"
                                        :seriesName="explorerData.breakdowns.district.label"
                                        :colors="pickCategorical(explorerData.breakdowns.district.data.length)"
                                        height="280px"
                                        :horizontal="explorerData.breakdowns.district.data.length > 5"
                                        @chart-click="(p) => addDimensionFilter('district', p.name || extractBarLabel(p))"
                                    />
                                </ZambiaMapChart>
                            </template>
                        </TabCard>
                    </div>

                    <!-- Row 2: Developer, Sub-Sector, Programme -->

                    <!-- Developer Chart (4 cols / 1/3 width) - Moved from Row 3 -->
                    <div v-if="explorerData.breakdowns.developer" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :class="['h-full', explorerData.breakdowns.developer.isFiltered && 'ring-2 ring-zesco-400/50']">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span><span class="hidden md:block">Projects by</span> {{ explorerData.breakdowns.developer.label }}</span>
                                    <span v-if="explorerData.breakdowns.developer.isFiltered" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :style="filterChipStyle">
                                        {{ explorerData.breakdowns.developer.activeFilter }}
                                    </span>
                                </div>
                            </template>
                            <template #default="{ zoomedHeight }">
                            <Pie3DChart
                                :data="explorerData.breakdowns.developer.data.map(d => ({ name: d.name, value: d.value }))"
                                :height="zoomedHeight || '280px'"
                                :show-legend="false"
                                @chart-click="(p) => addDimensionFilter('developer', p.name)"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Sub-Sector Chart (4 cols / 1/3 width) - Changed to Pie -->
                    <div v-if="explorerData.breakdowns.sub_sector" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :class="['h-full', explorerData.breakdowns.sub_sector.isFiltered && 'ring-2 ring-zesco-400/50']">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span>Projects by {{ explorerData.breakdowns.sub_sector.label }}</span>
                                    <span v-if="explorerData.breakdowns.sub_sector.isFiltered" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :style="filterChipStyle">
                                        {{ explorerData.breakdowns.sub_sector.activeFilter }}
                                    </span>
                                </div>
                            </template>
                            <template #default="{ zoomedHeight }">
                            <Pie3DChart
                                :data="explorerData.breakdowns.sub_sector.data.map(d => ({ name: d.name, value: d.value }))"
                                :height="zoomedHeight || '280px'"
                                :show-legend="false"
                                @chart-click="(p) => addDimensionFilter('sub_sector', p.name)"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Programme Chart (4 cols / 1/3 width) - Horizontal bar for better labels -->
                    <div v-if="explorerData.breakdowns.programme" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :class="['h-full', explorerData.breakdowns.programme.isFiltered && 'ring-2 ring-zesco-400/50']">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span>Projects by {{ explorerData.breakdowns.programme.label }}</span>
                                    <span v-if="explorerData.breakdowns.programme.isFiltered" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :style="filterChipStyle">
                                        {{ explorerData.breakdowns.programme.activeFilter }}
                                    </span>
                                </div>
                            </template>
                            <template #default="{ zoomedHeight }">
                            <BarChart
                                :data="explorerData.breakdowns.programme.data.map(d => ({ label: d.name, value: d.value }))"
                                xField="label"
                                yField="value"
                                :seriesName="explorerData.breakdowns.programme.label"
                                :colors="pickCategorical(explorerData.breakdowns.programme.data.length)"
                                :height="zoomedHeight || '280px'"
                                :horizontal="true"
                                @chart-click="(p) => addDimensionFilter('programme', p.name || extractBarLabel(p))"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Row 3: Sector, Contractor, Status, Risk Analysis -->

                    <!-- Sector Chart (4 cols / 1/3 width) - Hide when filtered -->
                    <div v-if="explorerData.breakdowns.sector && !explorerData.breakdowns.sector.isFiltered" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :title="`Projects by ${explorerData.breakdowns.sector.label}`" class="h-full">
                            <template #default="{ zoomedHeight }">
                            <Pie3DChart
                                :data="explorerData.breakdowns.sector.data.map(d => ({ name: d.name, value: d.value }))"
                                :height="zoomedHeight || '280px'"
                                :show-legend="false"
                                @chart-click="(p) => addDimensionFilter('sector', p.name)"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Contractor Chart (4 cols / 1/3 width) - Changed to Pie for better visualization -->
                    <div v-if="explorerData.breakdowns.contractor" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :class="['h-full', explorerData.breakdowns.contractor.isFiltered && 'ring-2 ring-zesco-400/50']">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span>Projects by {{ explorerData.breakdowns.contractor.label }}</span>
                                    <span v-if="explorerData.breakdowns.contractor.isFiltered" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :style="filterChipStyle">
                                        {{ explorerData.breakdowns.contractor.activeFilter }}
                                    </span>
                                </div>
                            </template>
                            <template #default="{ zoomedHeight }">
                            <Pie3DChart
                                :data="explorerData.breakdowns.contractor.data.map(d => ({ name: d.name, value: d.value }))"
                                :height="zoomedHeight || '280px'"
                                :show-legend="false"
                                @chart-click="(p) => addDimensionFilter('contractor', p.name)"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Status Chart (4 cols / 1/3 width) - Bar chart for progression states -->
                    <div v-if="explorerData.breakdowns.status" class="col-span-1 md:col-span-6 lg:col-span-4">
                        <ChartCard :class="['h-full', explorerData.breakdowns.status.isFiltered && 'ring-2 ring-zesco-400/50']">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <span>Projects by {{ explorerData.breakdowns.status.label }}</span>
                                    <span v-if="explorerData.breakdowns.status.isFiltered" 
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :style="filterChipStyle">
                                        {{ explorerData.breakdowns.status.activeFilter }}
                                    </span>
                                </div>
                            </template>
                            <template #default="{ zoomedHeight }">
                            <BarChart
                                :data="explorerData.breakdowns.status.data.map(d => ({ label: d.name, value: d.value }))"
                                xField="label"
                                yField="value"
                                :seriesName="explorerData.breakdowns.status.label"
                                :colors="pickCategorical(explorerData.breakdowns.status.data.length)"
                                :height="zoomedHeight || '280px'"
                                :horizontal="explorerData.breakdowns.status.data.length > 3"
                                @chart-click="(p) => addDimensionFilter('status', p.name || extractBarLabel(p))"
                            />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Risk Tabbed Card (4 cols / 1/3 width) - Moved from Row 2 -->
                    <div v-if="explorerData.risksByCategory.length > 0" class="col-span-1 sm:col-span-2 md:col-span-6 lg:col-span-4">
                        <TabCard
                            title="Risk Analysis"
                            :tabs="riskTabs"
                            class="h-full"
                        >
                            <template #actions>
                                <button
                                    @click="expandedChart = { type: 'risk', title: 'Risk Analysis' }"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                                    title="Expand chart"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>
                            </template>
                            <template #tab-category>
                                <Pie3DChart
                                    :data="riskCategoryData"
                                    height="280px"
                                    :show-legend="false"
                                />
                            </template>
                            <template #tab-status>
                                <BarChart
                                    :data="riskStatusData"
                                    xField="label"
                                    yField="value"
                                    seriesName="Risks"
                                    :colors="pickCategorical(riskStatusData.length)"
                                    height="280px"
                                />
                            </template>
                        </TabCard>
                    </div>

                    <!-- ── Investment Chart (always shown if sectors vary) ── -->
                    <div v-if="explorerData.sectorInvestment.length > 1" class="col-span-1 sm:col-span-2 md:col-span-6 lg:col-span-8">
                        <ChartCard title="Investment by Sector — Committed vs Paid (USD)">
                            <template #default="{ zoomedHeight }">
                            <BarChart
                                :data="investmentBarData"
                                xField="label"
                                :multiSeries="investmentSeries"
                                :height="zoomedHeight || '320px'"
                                @chart-click="(p) => addDimensionFilter('sector', p.name)"
                            />
                            </template>
                        </ChartCard>
                    </div>
                </div>
            </template>

            <!-- ── Grid Impact Studies Banner (shows when IPP sector is filtered) ── -->
            <div v-if="isIppContext" class="mb-8">
                <Link href="/pp/dashboard/grid-studies"
                      class="block bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl border border-purple-200 dark:border-purple-700/50 p-5 hover:shadow-lg transition-all duration-200 cursor-pointer group">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                Grid Impact Studies Tracker
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                View the dedicated 5-stage pipeline tracker for IPP grid connection studies
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>
        </template>

        <!-- ── Project Table (Classic View Only) ── -->
        <div v-if="viewMode === 'classic'" class="mb-6">
            <div class="mb-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Projects</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ explorerData.projects.length }} projects — click a project for full details
                </p>
            </div>

            <!-- Table search / sort controls -->
            <div class="flex items-center gap-3 mb-3 no-print">
                <Input
                    v-model="tableSearch"
                    type="text"
                    placeholder="Search projects…"
                    icon="search"
                    size="sm"
                    class="flex-1 max-w-md"
                />
                <Select
                    v-model="sortField"
                    :options="sortOptions"
                    size="sm"
                    class="w-48"
                />
            </div>

            <Card noPadding>
                <div class="p-4">
                    <ProjectStackedList
                        :projects="paginatedProjects"
                        empty-title="No projects match these filters"
                        empty-body="Try removing some filters or clear all."
                        :show-rag="true"
                        :show-key-issue="true"
                        :show-progress="false"
                        @select="goToProject"
                    />
                    <div v-if="filteredProjects.length === 0" class="text-center">
                        <button @click="clearAllFilters" class="text-sm text-zesco-600 hover:underline">Clear all filters</button>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500">
                        Showing {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, filteredProjects.length) }} of {{ filteredProjects.length }}
                    </p>
                    <div class="flex items-center gap-1">
                        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                                class="p-1.5 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <span class="text-xs text-gray-500 px-2">{{ currentPage }} / {{ totalPages }}</span>
                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage >= totalPages"
                                class="p-1.5 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Chart Expansion Modals -->
        <ChartModal :model-value="!!expandedChart" :title="expandedChart?.title || 'Chart'" @update:model-value="(val) => !val && (expandedChart = null)">
            <!-- Sector Chart Expanded -->
            <Pie3DChart
                v-if="expandedChart?.type === 'sector' && explorerData.breakdowns.sector"
                :data="explorerData.breakdowns.sector.data.map(d => ({ name: d.name, value: d.value }))"
                height="600px"
                @chart-click="(p) => { addDimensionFilter('sector', p.name); expandedChart = null; }"
            />
            
            <!-- Sub-Sector Chart Expanded -->
            <Pie3DChart
                v-else-if="expandedChart?.type === 'sub_sector' && explorerData.breakdowns.sub_sector"
                :data="explorerData.breakdowns.sub_sector.data.map(d => ({ name: d.name, value: d.value }))"
                height="600px"
                @chart-click="(p) => { addDimensionFilter('sub_sector', p.name); expandedChart = null; }"
            />
            
            <!-- Status Chart Expanded -->
            <BarChart
                v-else-if="expandedChart?.type === 'status' && explorerData.breakdowns.status"
                :data="explorerData.breakdowns.status.data.map(d => ({ label: d.name, value: d.value }))"
                xField="label"
                yField="value"
                :seriesName="explorerData.breakdowns.status.label"
                :colors="pickCategorical(explorerData.breakdowns.status.data.length)"
                height="600px"
                :horizontal="explorerData.breakdowns.status.data.length > 3"
                @chart-click="(p) => { addDimensionFilter('status', p.name || extractBarLabel(p)); expandedChart = null; }"
            />
            
            <!-- Programme Chart Expanded -->
            <BarChart
                v-else-if="expandedChart?.type === 'programme' && explorerData.breakdowns.programme"
                :data="explorerData.breakdowns.programme.data.map(d => ({ label: d.name, value: d.value }))"
                xField="label"
                yField="value"
                :seriesName="explorerData.breakdowns.programme.label"
                :colors="pickCategorical(explorerData.breakdowns.programme.data.length)"
                height="600px"
                :horizontal="true"
                @chart-click="(p) => { addDimensionFilter('programme', p.name || extractBarLabel(p)); expandedChart = null; }"
            />
            
            <!-- Contractor Chart Expanded -->
            <Pie3DChart
                v-else-if="expandedChart?.type === 'contractor' && explorerData.breakdowns.contractor"
                :data="explorerData.breakdowns.contractor.data.map(d => ({ name: d.name, value: d.value }))"
                height="600px"
                @chart-click="(p) => { addDimensionFilter('contractor', p.name); expandedChart = null; }"
            />
            
            <!-- Developer Chart Expanded -->
            <Pie3DChart
                v-else-if="expandedChart?.type === 'developer' && explorerData.breakdowns.developer"
                :data="explorerData.breakdowns.developer.data.map(d => ({ name: d.name, value: d.value }))"
                height="600px"
                @chart-click="(p) => { addDimensionFilter('developer', p.name); expandedChart = null; }"
            />
            
            <!-- Geographic Distribution Expanded -->
            <div v-else-if="expandedChart?.type === 'geographic'" class="space-y-6">
                <TabCard
                    title="Geographic Distribution"
                    :tabs="geographicTabs"
                >
                    <template #tab-province v-if="explorerData.breakdowns.province">
                        <ZambiaMapChart
                            :data="explorerData.breakdowns.province.data.map(d => ({ name: d.name, value: d.value, investment: d.totalCost || 0 }))"
                            level="province"
                            :metricLabel="explorerData.breakdowns.province.label"
                            :showToggle="true"
                            :showMetricToggle="true"
                            height="600px"
                            @region-click="(name) => { addDimensionFilter('province', name); expandedChart = null; }"
                        >
                            <BarChart
                                :data="explorerData.breakdowns.province.data.map(d => ({ label: d.name, value: d.value }))"
                                xField="label"
                                yField="value"
                                :seriesName="explorerData.breakdowns.province.label"
                                :colors="pickCategorical(explorerData.breakdowns.province.data.length)"
                                height="600px"
                                :horizontal="explorerData.breakdowns.province.data.length > 5"
                                @chart-click="(p) => { addDimensionFilter('province', p.name || extractBarLabel(p)); expandedChart = null; }"
                            />
                        </ZambiaMapChart>
                    </template>
                    <template #tab-district v-if="explorerData.breakdowns.district">
                        <ZambiaMapChart
                            :data="explorerData.breakdowns.district.data.map(d => ({ name: d.name, value: d.value, investment: d.totalCost || 0 }))"
                            level="district"
                            :metricLabel="explorerData.breakdowns.district.label"
                            :showToggle="true"
                            :showMetricToggle="true"
                            height="600px"
                            @region-click="(name) => { addDimensionFilter('district', name); expandedChart = null; }"
                        >
                            <BarChart
                                :data="explorerData.breakdowns.district.data.map(d => ({ label: d.name, value: d.value }))"
                                xField="label"
                                yField="value"
                                :seriesName="explorerData.breakdowns.district.label"
                                :colors="pickCategorical(explorerData.breakdowns.district.data.length)"
                                height="600px"
                                :horizontal="explorerData.breakdowns.district.data.length > 5"
                                @chart-click="(p) => { addDimensionFilter('district', p.name || extractBarLabel(p)); expandedChart = null; }"
                            />
                        </ZambiaMapChart>
                    </template>
                </TabCard>
            </div>
            
            <!-- Risk Analysis Expanded -->
            <div v-else-if="expandedChart?.type === 'risk'" class="space-y-6">
                <TabCard
                    title="Risk Analysis"
                    :tabs="riskTabs"
                >
                    <template #tab-category>
                        <Pie3DChart
                            :data="riskCategoryData"
                            height="600px"
                        />
                    </template>
                    <template #tab-status>
                        <BarChart
                            :data="riskStatusData"
                            xField="label"
                            yField="value"
                            seriesName="Risks"
                            :colors="pickCategorical(riskStatusData.length)"
                            height="600px"
                        />
                    </template>
                </TabCard>
            </div>
            
            <!-- Projects List Expanded -->
            <div v-else-if="expandedChart?.type === 'projects'" class="space-y-4">
                <!-- Search / Sort Controls -->
                <div class="flex items-center gap-3">
                    <Input
                        v-model="tableSearch"
                        type="text"
                        placeholder="Search projects…"
                        icon="search"
                        size="sm"
                        class="flex-1 max-w-md"
                    />
                    <Select
                        v-model="sortField"
                        :options="sortOptions"
                        size="sm"
                        class="w-48"
                    />
                </div>

                <!-- Projects List -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-4">
                        <ProjectStackedList
                            :projects="paginatedProjects"
                            empty-title="No projects match these filters"
                            empty-body="Try removing some filters or clear all."
                            :show-rag="true"
                            :show-key-issue="true"
                            :show-progress="false"
                            @select="(id) => { goToProject(id); expandedChart = null; }"
                        />
                        <div v-if="filteredProjects.length === 0" class="text-center">
                            <button @click="clearAllFilters" class="text-sm text-zesco-600 hover:underline">Clear all filters</button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-500">
                            Showing {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, filteredProjects.length) }} of {{ filteredProjects.length }}
                        </p>
                        <div class="flex items-center gap-1">
                            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                                    class="p-1.5 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <span class="text-xs text-gray-500 px-2">{{ currentPage }} / {{ totalPages }}</span>
                            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage >= totalPages"
                                    class="p-1.5 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </ChartModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, defineAsyncComponent, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Breadcrumb from '@/Components/UI/Breadcrumb.vue';
import Card from '@/Components/UI/Card.vue';
import ChartCard from '@/Components/UI/ChartCard.vue';
import TabCard from '@/Components/UI/TabCard.vue';
import ChartModal from '@/Components/UI/ChartModal.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Select from '@/Components/UI/Select.vue';
import Input from '@/Components/UI/Input.vue';
import ProjectStackedList from '@/Components/UI/ProjectStackedList.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import { INVESTMENT, RAG } from '@/Composables/useChartPalette';
import { useChartPalettes } from '@/Composables/useChartPalettes';
import { useDirectorateStore } from '@/stores/useDirectorateStore';

const directorateStore = useDirectorateStore();


const Pie3DChart = defineAsyncComponent(() => import('@/Components/Charts/Pie3DChart.vue'));
const ZambiaMapChart = defineAsyncComponent(() => import('@/Components/Charts/ZambiaMapChart.vue'));

const { pickCategorical, twoTone } = useChartPalettes();

const props = defineProps({
    explorerData: { type: Object, required: true },
    directorate: { type: Object, required: true },
    directorates: { type: Array, default: () => [] },
    dimensionLabels: { type: Object, default: () => ({}) },
});

// ── View Mode Management (Classic vs Compact) ──
const viewMode = computed({
    get: () => directorateStore.explorerViewMode,
    set: (val) => directorateStore.setExplorerViewMode(val),
});

// Expanded chart modal state
const expandedChart = ref(null);

// Palette-based filter chip style
const filterChipStyle = computed(() => ({
    backgroundColor: 'var(--palette-accent-lighter)',
    color: 'var(--palette-accent-dark)',
}));

// Load view mode from localStorage on mount
onMounted(() => {
    const savedMode = localStorage.getItem('pp_explorer_view_mode');
    if (savedMode === 'compact' || savedMode === 'classic') {
        directorateStore.setExplorerViewMode(savedMode);
    }
    // Auto-enter directorate mode if not already active (e.g. direct URL navigation)
    if (!directorateStore.activeDirectorate) {
        directorateStore.enterDirectorate(props.directorate);
    }
    // Populate explorer filter state for sidebar
    syncExplorerFiltersToStore();
});

onUnmounted(() => {
    directorateStore.clearExplorerFilters();
});

function syncExplorerFiltersToStore() {
    directorateStore.setExplorerFilters({
        appliedFilters: props.explorerData.appliedFilters || {},
        filterOptions: props.explorerData.filterOptions || {},
        dimensionLabels: props.dimensionLabels || {},
    });
}

// Keep store in sync when Inertia page data changes
watch(() => props.explorerData, () => {
    syncExplorerFiltersToStore();
}, { deep: true });

// Save view mode to localStorage when changed
watch(viewMode, (newMode) => {
    localStorage.setItem('pp_explorer_view_mode', newMode);
});

// Geographic tabs for compact view
const geographicTabs = computed(() => {
    const tabs = [];
    if (props.explorerData.breakdowns?.province) {
        tabs.push({ key: 'province', label: 'Province' });
    }
    if (props.explorerData.breakdowns?.district) {
        tabs.push({ key: 'district', label: 'District' });
    }
    return tabs;
});

// Auto-switch to district tab when province is filtered
const geographicActiveTab = ref('province');

// Watch for province filter to auto-switch to district tab
watch(() => props.explorerData.appliedFilters?.province, (provinceFilter) => {
    if (provinceFilter && props.explorerData.breakdowns?.district && !props.explorerData.breakdowns?.district?.isFiltered) {
        // Province is filtered and districts are available (not filtered), switch to district tab
        geographicActiveTab.value = 'district';
    } else if (!provinceFilter) {
        // Province filter removed, switch back to province tab
        geographicActiveTab.value = 'province';
    }
}, { immediate: true });

// Risk tabs for compact view
const riskTabs = [
    { key: 'category', label: 'By Category' },
    { key: 'status', label: 'By Status' },
];

// Helper to get active geographic filter (province or district)
const activeGeographicFilter = computed(() => {
    if (props.explorerData.breakdowns?.province?.isFiltered) {
        return { label: 'Province', value: props.explorerData.breakdowns.province.activeFilter };
    }
    if (props.explorerData.breakdowns?.district?.isFiltered) {
        return { label: 'District', value: props.explorerData.breakdowns.district.activeFilter };
    }
    return null;
});

const headerMetrics = computed(() => {
    const spendColor = (props.explorerData.kpis?.spendPct ?? 0) >= 50 ? RAG.green : RAG.amber;
    const progressColor = (props.explorerData.kpis?.avgProgress ?? 0) >= 50 ? RAG.green : RAG.amber;
    return [
        { label: 'Projects', value: props.explorerData.kpis?.totalProjects ?? 0 },
        { label: 'Committed', value: `$${fmtM(props.explorerData.kpis?.totalCommitted)}`, color: INVESTMENT.committed },
        { label: 'Spend', value: `${props.explorerData.kpis?.spendPct ?? 0}%`, color: spendColor },
        { label: 'Avg Progress', value: `${props.explorerData.kpis?.avgProgress ?? 0}%`, color: progressColor },
    ];
});

// ── Breadcrumb items ──

const breadcrumbItems = computed(() => {
    const items = [
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'PP Portfolio', href: '/pp/dashboard' }
    ];
    
    // Add filter items dynamically
    for (const [dim, val] of Object.entries(props.explorerData.appliedFilters || {})) {
        items.push({ label: val, href: breadcrumbUrl(dim) });
    }
    
    return items;
});

// ── Filter management ──

const hasFilters = computed(() => Object.keys(props.explorerData.appliedFilters || {}).length > 0);

const isIppContext = computed(() => {
    const applied = props.explorerData.appliedFilters || {};
    return applied.sector === 'IPP';
});

const filterSummaryTitle = computed(() => {
    const parts = Object.entries(props.explorerData.appliedFilters || {}).map(([dim, val]) => val);
    return parts.join(' › ');
});

const availableDimensions = computed(() => {
    const applied = props.explorerData.appliedFilters || {};
    const result = {};
    for (const [dim, label] of Object.entries(props.dimensionLabels)) {
        if (!applied[dim]) {
            // Only show if there are options with data
            const opts = props.explorerData.filterOptions?.[dim] || [];
            if (opts.length > 0) {
                result[dim] = label;
            }
        }
    }
    return result;
});

const availableDimensionsOptions = computed(() => {
    return Object.entries(availableDimensions.value).map(([value, label]) => ({
        value,
        label
    }));
});

const addFilterDim = ref('');
const addFilterVal = ref('');

const addFilterOptions = computed(() => {
    if (!addFilterDim.value) return [];
    return (props.explorerData.filterOptions?.[addFilterDim.value] || []).map(opt => ({
        value: opt,
        label: opt
    }));
});

watch(addFilterDim, () => { addFilterVal.value = ''; });

// Watch for value change since our custom component emits update:modelValue
watch(addFilterVal, (newVal) => {
    if (newVal) {
        applyAddedFilter();
    }
});

function applyAddedFilter() {
    if (!addFilterDim.value || !addFilterVal.value) return;
    addDimensionFilter(addFilterDim.value, addFilterVal.value);
    addFilterDim.value = '';
    addFilterVal.value = '';
}

function addDimensionFilter(dim, value) {
    if (!value || value === 'Unknown') return;
    const current = { ...(props.explorerData.appliedFilters || {}) };
    
    // If clicking the currently active filter value, remove the filter
    if (current[dim] === value) {
        delete current[dim];
        if (Object.keys(current).length === 0) {
            router.get('/pp/dashboard/explore');
        } else {
            router.get('/pp/dashboard/explore', current);
        }
    } else {
        // Otherwise, set or change the filter
        current[dim] = value;
        router.get('/pp/dashboard/explore', current);
    }
}

function removeFilter(dim) {
    const current = { ...(props.explorerData.appliedFilters || {}) };
    delete current[dim];
    if (Object.keys(current).length === 0) {
        router.get('/pp/dashboard/explore');
    } else {
        router.get('/pp/dashboard/explore', current);
    }
}

function clearAllFilters() {
    router.get('/pp/dashboard/explore');
}

function breadcrumbUrl(upToDim) {
    const filters = {};
    for (const [dim, val] of Object.entries(props.explorerData.appliedFilters || {})) {
        filters[dim] = val;
        if (dim === upToDim) break;
    }
    return '/pp/dashboard/explore?' + new URLSearchParams(filters).toString();
}

// ── Chart data transforms ──

const investmentBarData = computed(() => {
    return (props.explorerData.sectorInvestment || []).map(item => ({
        label: item.sector,
        committed: item.committed || 0,
        paid: item.paid || 0,
    }));
});

const investmentSeries = computed(() => ([
    { name: 'Committed (USD)', field: 'committed', color: twoTone.value?.[0] },
    { name: 'Paid (USD)', field: 'paid', color: twoTone.value?.[1] },
]));

const riskCategoryData = computed(() => {
    return (props.explorerData.risksByCategory || []).map((item) => ({
        name: item.category,
        value: item.count,
    }));
});

const riskLevelData = computed(() => {
    const colorMap = { Green: RAG.green, Amber: RAG.amber, Red: RAG.red };
    return (props.explorerData.risksByLevel || []).map(item => ({
        label: item.level,
        value: item.count,
        color: colorMap[item.level] || RAG.grey,
    }));
});

const riskStatusData = computed(() => {
    return (props.explorerData.risksByStatus || []).map(item => ({
        label: item.status,
        value: item.count,
    }));
});

// ── Table management ──

const tableSearch = ref('');
const sortField = ref('cost_usd');
const sortOptions = [
    { value: 'cost_usd', label: 'Sort: Cost (high→low)' },
    { value: 'name', label: 'Sort: Name' },
    { value: 'progress_pct', label: 'Sort: Progress' },
    { value: 'capacity_mw', label: 'Sort: MW' },
];
const currentPage = ref(1);
const pageSize = 15;

const filteredProjects = computed(() => {
    let list = [...(props.explorerData.projects || [])];

    // Search
    if (tableSearch.value) {
        const q = tableSearch.value.toLowerCase();
        list = list.filter(p =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.code || '').toLowerCase().includes(q) ||
            (p.sector || '').toLowerCase().includes(q) ||
            (p.province || '').toLowerCase().includes(q) ||
            (p.district || '').toLowerCase().includes(q)
        );
    }

    // Sort
    list.sort((a, b) => {
        if (sortField.value === 'name') return (a.name || '').localeCompare(b.name || '');
        if (sortField.value === 'progress_pct') return (b.progress_pct || 0) - (a.progress_pct || 0);
        if (sortField.value === 'capacity_mw') return (b.capacity_mw || 0) - (a.capacity_mw || 0);
        return (b.cost_usd || 0) - (a.cost_usd || 0);
    });

    return list;
});

const totalPages = computed(() => Math.ceil(filteredProjects.value.length / pageSize));

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredProjects.value.slice(start, start + pageSize);
});

// Reset page on search change
watch(tableSearch, () => { currentPage.value = 1; });
watch(sortField, () => { currentPage.value = 1; });

function goToProject(id) {
    const filters = { ...(props.explorerData.appliedFilters || {}) };
    router.get(`/pp/dashboard/projects/${id}`, filters);
}

function extractBarLabel(params) {
    return params.name || params.data?.label || '';
}

// ── Helpers ──

function fmtM(val) {
    const n = Number(val) || 0;
    if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return n.toLocaleString();
}
</script>
