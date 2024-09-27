<template>
  <div class="wrapper">
    <h1 class="purple">Task Manager</h1>

    <!-- Notification message -->
    <transition name="fade">
      <div v-if="notification.message" :class="notification.type" class="notification">
        {{ notification.message }}
      </div>
    </transition>

    <div class="background-container">
      <div class="columns">
        <!-- First column: Task creation form -->
        <div class="form-column">
          <form @submit.prevent="createTask">
            <label for="task-title">Task Title</label>
            <input id="task-title" v-model="title" required />

            <label for="task-description">Task Description</label>
            <textarea id="task-description" v-model="description" required></textarea>

            <button class="btn-submit" type="submit">Add Task</button>
          </form>
        </div>

        <!-- Second column: Task list -->
        <div class="task-column">
          <ul v-if="tasks.length > 0">
            <li class="task" v-for="task in tasks" :key="task.id">
              <div>
                <p class="capitalize"><strong>Status:</strong> {{ task.status }}</p>
                <p><strong>Title:</strong> {{ task.title }}</p>
                <p><strong>Description:</strong> {{ task.description }}</p>
              </div>
              <div>
                <button class="btn-edit" @click="openEditModal(task)">Edit</button>
                <button class="btn-delete" @click="deleteTask(task.id)">Delete</button>
              </div>
            </li>
          </ul>
          <p v-else class="task-column-no-tasks">No tasks</p>
          <!-- Message displayed when there are no tasks -->
        </div>
      </div>
    </div>

    <!-- Modal for editing task -->
    <div v-if="isEditModalOpen" class="modal-overlay">
      <div class="modal-content">
        <h2 class="purple">Edit Task</h2>

        <label for="edit-task-title">Task Title</label>
        <input id="edit-task-title" v-model="editTitle" required />

        <label for="edit-task-description">Task Description</label>
        <textarea id="edit-task-description" v-model="editDescription" required></textarea>

        <label for="edit-task-status">Status</label>
        <select id="edit-task-status" v-model="editStatus" class="modal-select">
          <option value="pending">Pending</option>
          <option value="in-progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>

        <div class="modal-buttons">
          <button class="btn-edit" @click="updateTask">Save</button>
          <button class="btn-delete" @click="closeEditModal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

// Define types for tasks and notification
interface Task {
  id: number
  title: string
  description: string
  status: string
}

interface Notification {
  message: string
  type: 'success' | 'error'
}

// Reactive variables
const title = ref<string>('')
const description = ref<string>('')
const tasks = ref<Array<Task>>([])

// Variables for edit modal
const isEditModalOpen = ref<boolean>(false)
const editTaskId = ref<number | null>(null)
const editTitle = ref<string>('')
const editDescription = ref<string>('')
const editStatus = ref<string>('pending')

// Initialize the notification object instead of null
const notification = ref<Notification>({ message: '', type: 'success' })

const API_URL = 'http://localhost:8080/tasks.php'

// Function to show notification for 2 seconds
const showNotification = (message: string, type: 'success' | 'error') => {
  notification.value.message = message
  notification.value.type = type
  setTimeout(() => {
    notification.value.message = ''
  }, 2000) // 2 seconds
}

// Open the modal with task data for editing
const openEditModal = (task: Task) => {
  editTaskId.value = task.id
  editTitle.value = task.title
  editDescription.value = task.description
  editStatus.value = task.status
  isEditModalOpen.value = true
}

// Close the edit modal
const closeEditModal = () => {
  isEditModalOpen.value = false
  editTaskId.value = null
}

const createTask = async () => {
  const taskData = {
    title: title.value,
    description: description.value
  }
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(taskData)
    })

    const result = await response.json()
    if (response.ok) {
      tasks.value.push({
        id: result.id,
        title: title.value,
        description: description.value,
        status: 'pending'
      })
      title.value = ''
      description.value = ''
      showNotification('Task added successfully', 'success')
    } else {
      showNotification(result.error, 'error')
    }
  } catch (error) {
    console.error('Error adding task:', error)
    showNotification('Error adding task', 'error')
  }
}

const fetchTasks = async () => {
  try {
    const response = await fetch(API_URL, { method: 'GET' })
    const result = await response.json()
    if (response.ok) {
      tasks.value = result
    } else {
      showNotification(result.error, 'error')
    }
  } catch (error) {
    console.error('Error fetching tasks:', error)
    showNotification('Error fetching tasks', 'error')
  }
}

onMounted(async () => {
  await fetchTasks()
})

const updateTask = async () => {
  if (editTaskId.value !== null) {
    const updatedTask = {
      id: editTaskId.value,
      title: editTitle.value,
      description: editDescription.value,
      status: editStatus.value
    }

    try {
      const response = await fetch(API_URL, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(updatedTask)
      })

      const result = await response.json()
      if (response.ok) {
        showNotification('Task updated successfully', 'success')
        fetchTasks() // Reload the tasks
        closeEditModal()
      } else {
        showNotification(result.error, 'error')
      }
    } catch (error) {
      console.error('Error updating task:', error)
      showNotification('Error updating task', 'error')
    }
  }
}

const deleteTask = async (taskId: number) => {
  try {
    const response = await fetch(API_URL, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id: taskId })
    })

    const result = await response.json()
    if (response.ok) {
      showNotification('Task deleted successfully', 'success')
      fetchTasks() // Reload the tasks
    } else {
      showNotification(result.error, 'error')
    }
  } catch (error) {
    console.error('Error deleting task:', error)
    showNotification('Error deleting task', 'error')
  }
}
</script>

<style scoped>
.background-container {
  background-color: rgba(120, 55, 195, 0.2);
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(120, 55, 195, 0.2);
}

h1 {
  text-align: center;
}

h2 {
  text-align: center;
}

.capitalize {
  text-transform: capitalize;
}

.wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  position: relative;
}

.columns {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 20px;
  width: 100%;
  max-width: 800px;
}

.task-column-no-tasks {
  text-align: center;
  font-weight: bold;
}

.form-column,
.task-column {
  flex: 1 1 300px;
  min-width: 200px;
  max-width: 100%;
  margin: auto;
}

.task-column {
  max-height: 400px;
  overflow-y: auto;
}

@supports selector(::-webkit-scrollbar) {
  /* Custom scrollbar styles */
  .task-column::-webkit-scrollbar {
    width: 4px;
    padding: 0.5em;
  }

  .task-column::-webkit-scrollbar-track {
    background: rgba(120, 55, 195, 0.1);
  }

  .task-column::-webkit-scrollbar-thumb {
    background: #7937c5;
    border-radius: 4px;
  }

  .task-column::-webkit-scrollbar-thumb:hover {
    background: #442bd3;
  }
}
/* For Firefox */
.task-column {
  scrollbar-width: thin;
  scrollbar-color: #7937c5 rgba(120, 55, 195, 0.1);
  padding: 0.5em;
}

ul {
  padding: 0;
  list-style-type: none;
  margin: 0;
}

li {
  margin-bottom: 0.5em;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  max-width: 100%;
  overflow-wrap: break-word;
  word-wrap: break-word;
  word-break: normal;
}

label {
  margin: 0.5em 0;
  font-weight: bold;
}

input,
textarea {
  height: 40px;
  width: 100%;
  padding: 10px;
  box-sizing: border-box;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin-bottom: 1em;
}

textarea {
  height: 80px;
}

button {
  padding: 10px 20px;
  background-color: #7937c5;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition:
    background-color 0.3s ease,
    transform 0.2s ease;
}

button :active {
  background-color: #622ca0;
  transform: scale(0.95);
}

button:hover {
  background-color: #622ca0;
  transform: scale(1.05);
}

.btn-submit {
  margin-bottom: 1.5em;
  width: 100%;
}
.btn-edit,
.btn-delete {
  display: inline-block;
  align-items: center;
  text-align: center;
  height: 30px;
  width: 100px;
  padding: 0 10px;
}
.btn-edit {
  background-color: #442bd3;
  margin-right: 1em;
}

.btn-edit:hover {
  background-color: rgb(55, 32, 155);
}

.btn-delete {
  background-color: #f44336;
}

.btn-delete:hover {
  background-color: #d32f2f;
}

.task {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

/* Notification styles */
.notification {
  position: fixed;
  top: 10px;
  padding: 10px;
  border-radius: 5px;
  text-align: center;
  opacity: 1;
  transition: opacity 0.5s ease-in-out;
  z-index: 1000;
}

.success {
  background-color: #4caf50;
  color: white;
}

.error {
  background-color: #f44336;
  color: white;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: #222222;
  padding: 20px;
  border-radius: 10px;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}

.modal-select {
  width: 100%;
  padding: 10px;
  box-sizing: border-box;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin-bottom: 1em;
}

.modal-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
