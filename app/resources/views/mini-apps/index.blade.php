@extends('layouts.app')

@section('page-title', 'Mini Apps Workspace')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-puzzle"></i> Mini Apps Workspace</h5>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addAppModal">
                        + Add App
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- WHT Software App -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-calculator"></i> WHT Software</h5>
                                <p class="card-text text-muted">Withholding Tax management and certificate generation</p>
                                <button class="btn btn-sm btn-primary" onclick="launchApp('wht')">
                                    <i class="bi bi-box-arrow-up-right"></i> Launch
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder for future apps -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary opacity-50">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-plus-circle"></i> Coming Soon</h5>
                                <p class="card-text text-muted">Additional tools will be added here</p>
                                <button class="btn btn-sm btn-secondary" disabled>Coming Soon</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Directory -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Available Apps</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>App Name</th>
                                <th>Description</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>WHT Software</strong></td>
                                <td>Withholding Tax certificate management</td>
                                <td>1.0</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="launchApp('wht')">
                                        Launch
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add App Modal -->
<div class="modal fade" id="addAppModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Mini App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" class="form-control" placeholder="e.g., Document Generator">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" placeholder="App description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL/Endpoint</label>
                        <input type="url" class="form-control" placeholder="https://...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Add App</button>
            </div>
        </div>
    </div>
</div>

<script>
function launchApp(appName) {
    alert('Launching ' + appName + ' app...');
    // App launch logic here
}
</script>
@endsection
