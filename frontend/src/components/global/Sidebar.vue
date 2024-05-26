<script setup>
import { ref } from 'vue'
const array = ref(["Menu 1", "Menu 2", "Menu 3", "Menu 4"])
</script>
<template>
    <div id="sidebar">
        <div class="array-list">
            <div v-for="x in array" >
                <div class="array-item">
                    {{ x }}
                </div>
            </div>
        </div>
    </div>
</template>
  
  <style scoped>
    #sidebar{
        width: 220px;
        padding: 12px;
        height: 100%;
        padding-top: 32px;
    }

    .array-item{
        padding: 12px;
        border-radius: 8px;
    }
    .array-list{
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .array-item:hover{
        cursor: pointer;
        background-color: #f16758;
        color: black;

    }
  </style>
  