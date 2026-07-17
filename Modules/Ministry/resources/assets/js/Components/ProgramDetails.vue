<template>
    <div class="card">
        <div class="card-header">
            Program Information
        </div>
        <ProgramInformationEdit v-bind="$attrs" :results="results" />
    </div>
</template>
<script>
import ProgramInformationEdit from "./ProgramInformationEdit";

export default {
    name: 'ProgramDetails',
    components: {
        ProgramInformationEdit
    },
    props: {
        results: Object
    }
}
</script>
